<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Consumer;

use GhostUnicorns\CrtActivity\Model\ActivityStateInterface;
use GhostUnicorns\CrtAmqp\Model\AmqpStateInterface;
use GhostUnicorns\CrtAmqp\Model\GenerateAndPublishAmqpCollectors;
use GhostUnicorns\CrtAmqp\Model\GenerateAndPublishAmqpRefiners;
use GhostUnicorns\CrtAmqp\Model\Status\ActivityCheckIsTimeToMakeAsCollectedDequeued;
use GhostUnicorns\CrtBase\Api\CollectorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use GhostUnicorns\CrtActivity\Api\ActivityRepositoryInterface;
use GhostUnicorns\CrtAmqp\Api\Data\CollectorInfoInterface;
use GhostUnicorns\CrtBase\Api\CrtListInterface;
use GhostUnicorns\CrtBase\Exception\CrtException;
use GhostUnicorns\CrtAmqpCollector\Api\CollectorRepositoryInterface;

class CollectorConsumer
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
     * @var ActivityCheckIsTimeToMakeAsCollectedDequeued
     */
    private $activityCheckIsTimeToMakeAsCollectedDequeued;

    /**
     * @var CollectorRepositoryInterface
     */
    private $collectorRepository;

    /**
     * @var GenerateAndPublishAmqpCollectors
     */
    private $generateAndPublishAmqpCollectors;

    /**
     * @var GenerateAndPublishAmqpRefiners
     */
    private $generateAndPublishAmqpRefiners;

    /**
     * @param CrtListInterface $crtList
     * @param ActivityRepositoryInterface $activityRepository
     * @param ActivityCheckIsTimeToMakeAsCollectedDequeued $activityCheckIsTimeToMakeAsCollectedDequeued
     * @param CollectorRepositoryInterface $collectorRepository
     * @param GenerateAndPublishAmqpCollectors $generateAndPublishAmqpCollectors
     * @param GenerateAndPublishAmqpRefiners $generateAndPublishAmqpRefiners
     */
    public function __construct(
        CrtListInterface $crtList,
        ActivityRepositoryInterface $activityRepository,
        ActivityCheckIsTimeToMakeAsCollectedDequeued $activityCheckIsTimeToMakeAsCollectedDequeued,
        CollectorRepositoryInterface $collectorRepository,
        GenerateAndPublishAmqpCollectors $generateAndPublishAmqpCollectors,
        GenerateAndPublishAmqpRefiners $generateAndPublishAmqpRefiners
    ) {
        $this->crtList = $crtList;
        $this->activityRepository = $activityRepository;
        $this->activityCheckIsTimeToMakeAsCollectedDequeued = $activityCheckIsTimeToMakeAsCollectedDequeued;
        $this->collectorRepository = $collectorRepository;
        $this->generateAndPublishAmqpCollectors = $generateAndPublishAmqpCollectors;
        $this->generateAndPublishAmqpRefiners = $generateAndPublishAmqpRefiners;
    }

    /**
     * @param CollectorInfoInterface $collectorInfo
     * @throws NoSuchEntityException|CrtException
     */
    public function process(CollectorInfoInterface $collectorInfo): void
    {
        $activityId = $collectorInfo->getActivityId();
        $collectorType = $collectorInfo->getCollectorType();

        $activity = $this->activityRepository->getById($activityId);
        $activityType = $activity->getType();

        if ($activity->getStatus() !== ActivityStateInterface::COLLECTING) {
            throw new NoSuchEntityException(__(
                'Unable to run CollectorConsumer for activity id %1 - Activity status in wrong',
                $activityId
            ));
        }

        try {
            $this->collectorRepository->update(
                $activityId,
                $collectorType,
                AmqpStateInterface::IN_DEQUEUE
            );

            $collectorList = $this->crtList->getCollectorList($activityType);
            $collectors = $collectorList->getCollectors();

            if (!array_key_exists($collectorType, $collectors)) {
                throw new NoSuchEntityException(__(
                    'Unable to find Collector with type %1',
                    $activityType
                ));
            }
            $collector = $collectors[$collectorType];

            /** @var CollectorInterface $collectorModel */
            $collectorModel = $collector['model'];

            $collectorModel->execute($activityId, $collectorType);

            $this->collectorRepository->update(
                $activityId,
                $collectorType,
                AmqpStateInterface::DEQUEUED
            );

            $this->generateAndPublishAmqpCollectors->execute($activityId);

            $isTimeToMakeAsCollected = $this->activityCheckIsTimeToMakeAsCollectedDequeued->execute($activityId);

            if ($isTimeToMakeAsCollected) {
                $this->generateAndPublishAmqpRefiners->execute($activityId);
            }
        } catch (\Exception $e) {
            $activity->setStatus(ActivityStateInterface::COLLECT_ERROR);
            $activity->addExtraArray(
                [
                    'collector_' . $collectorType => 'error',
                    'error' => $e->getMessage()
                ]
            );
            $this->activityRepository->save($activity);

            $this->collectorRepository->update(
                $activityId,
                $collectorType,
                AmqpStateInterface::DEQUEUED_ERROR
            );
            throw $e;
        }
    }
}
