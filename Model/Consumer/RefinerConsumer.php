<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Consumer;

use Exception;
use GhostUnicorns\CrtActivity\Model\ActivityStateInterface;
use GhostUnicorns\CrtAmqp\Model\AmqpStateInterface;
use GhostUnicorns\CrtAmqp\Model\GenerateAndPublishAmqpTransferors;
use GhostUnicorns\CrtAmqp\Model\Status\ActivityCheckIsTimeToMakeAsRefinedDequeued;
use GhostUnicorns\CrtBase\Api\CrtConfigInterface;
use GhostUnicorns\CrtBase\Exception\CrtImportantException;
use GhostUnicorns\CrtEntity\Api\EntityRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use GhostUnicorns\CrtActivity\Api\ActivityRepositoryInterface;
use GhostUnicorns\CrtAmqp\Api\Data\RefinerInfoInterface;
use GhostUnicorns\CrtBase\Api\CrtListInterface;
use GhostUnicorns\CrtBase\Exception\CrtException;
use GhostUnicorns\CrtAmqpRefiner\Api\RefinerRepositoryInterface;
use Psr\Log\LoggerInterface;

class RefinerConsumer
{
    /**
     * @var CrtListInterface
     */
    private $crtList;

    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    /**
     * @var RefinerRepositoryInterface
     */
    private $refinerRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $entityRepository;

    /**
     * @var ActivityCheckIsTimeToMakeAsRefinedDequeued
     */
    private $activityCheckIsTimeToMakeAsRefinedDequeued;

    /**
     * @var GenerateAndPublishAmqpTransferors
     */
    private $generateAndPublishAmqpTransferors;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CrtConfigInterface
     */
    private $config;

    /**
     * @param CrtListInterface $crtList
     * @param ActivityRepositoryInterface $activityRepository
     * @param RefinerRepositoryInterface $refinerRepository
     * @param EntityRepositoryInterface $entityRepository
     * @param ActivityCheckIsTimeToMakeAsRefinedDequeued $activityCheckIsTimeToMakeAsRefinedDequeued
     * @param GenerateAndPublishAmqpTransferors $generateAndPublishAmqpTransferors
     * @param LoggerInterface $logger
     * @param CrtConfigInterface $config
     */
    public function __construct(
        CrtListInterface $crtList,
        ActivityRepositoryInterface $activityRepository,
        RefinerRepositoryInterface $refinerRepository,
        EntityRepositoryInterface $entityRepository,
        ActivityCheckIsTimeToMakeAsRefinedDequeued $activityCheckIsTimeToMakeAsRefinedDequeued,
        GenerateAndPublishAmqpTransferors $generateAndPublishAmqpTransferors,
        LoggerInterface $logger,
        CrtConfigInterface $config
    ) {
        $this->crtList = $crtList;
        $this->activityRepository = $activityRepository;
        $this->refinerRepository = $refinerRepository;
        $this->entityRepository = $entityRepository;
        $this->activityCheckIsTimeToMakeAsRefinedDequeued = $activityCheckIsTimeToMakeAsRefinedDequeued;
        $this->generateAndPublishAmqpTransferors = $generateAndPublishAmqpTransferors;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @param RefinerInfoInterface $refinerInfo
     * @throws NoSuchEntityException|CrtException|CrtImportantException
     */
    public function process(RefinerInfoInterface $refinerInfo): void
    {
        $activityId = $refinerInfo->getActivityId();

        $activity = $this->activityRepository->getById($activityId);

        if ($activity->getStatus() !== ActivityStateInterface::COLLECTED &&
            $activity->getStatus() !== ActivityStateInterface::REFINING) {
            throw new NoSuchEntityException(__(
                'Unable to run RefinerConsumer for activity id %1 - Activity status in wrong',
                $activityId
            ));
        }

        $activity->setStatus(ActivityStateInterface::REFINING);
        $this->activityRepository->save($activity);

        $activityType = $activity->getType();
        $entityIdentifier = $refinerInfo->getEntityIdentifier();

        try {
            $this->refinerRepository->update(
                $activityId,
                $entityIdentifier,
                AmqpStateInterface::IN_DEQUEUE
            );

            $refinerList = $this->crtList->getRefinerList($activityType);
            $refiners = $refinerList->getRefiners();

            $allActivityEntities = $this->entityRepository->getAllByActivityIdGroupedByIdentifier($activityId);

            if (!array_key_exists($entityIdentifier, $allActivityEntities)) {
                throw new NoSuchEntityException(__(
                    'Unable to find Refiner with type %1',
                    $activityType
                ));
            }
            $entities = $allActivityEntities[$entityIdentifier];

            foreach ($refiners as $refinerType => $refiner) {
                $this->logger->debug(__(
                    'activityId:%1 ~ Refiner ~ refinerType:%2 ~ entityIdentifier:%3 ~ START',
                    $activityId,
                    $refinerType,
                    $entityIdentifier
                ));
                try {
                    $refinerModel = $refiner['model'];
                    $refinerModel->execute($activityId, $refinerType, $entityIdentifier, $entities);
                    $this->logger->debug(__(
                        'activityId:%1 ~ Refiner ~ refinerType:%2 ~ entityIdentifier:%3 ~ END',
                        $activityId,
                        $refinerType,
                        $entityIdentifier
                    ));
                } catch (CrtException $e) {
                    $this->logger->error(__(
                        'activityId:%1 ~ Refiner ~ refinerType:%2 ~ entityIdentifier:%3 ~ END with error ~ message:%4',
                        $activityId,
                        $refinerType,
                        $entityIdentifier,
                        $e->getMessage()
                    ));
                }
            }

            foreach ($entities as $entity) {
                $this->entityRepository->save($entity);
            }

            $this->refinerRepository->update(
                $activityId,
                $entityIdentifier,
                AmqpStateInterface::DEQUEUED
            );

            $isTimeToMakeAsRefined = $this->activityCheckIsTimeToMakeAsRefinedDequeued->execute($activityId);

            if ($isTimeToMakeAsRefined) {
                $this->generateAndPublishAmqpTransferors->execute($activityId);
            }
        } catch (Exception $e) {
            $this->refinerRepository->update(
                $activityId,
                $entityIdentifier,
                AmqpStateInterface::DEQUEUED_ERROR
            );

            if (!$this->config->continueInCaseOfErrors()) {
                $activity->setStatus(ActivityStateInterface::REFINE_ERROR);
                $activity->addExtraArray(
                    [
                        'refiner_' . $entityIdentifier => 'error',
                        'error' => $e->getMessage()
                    ]
                );
                $this->activityRepository->save($activity);
                throw $e;
            }
        }
    }
}
