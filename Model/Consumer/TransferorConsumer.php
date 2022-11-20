<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Consumer;

use GhostUnicorns\CrtActivity\Model\ActivityStateInterface;
use GhostUnicorns\CrtAmqp\Model\AmqpStateInterface;
use GhostUnicorns\CrtAmqp\Model\GenerateAndPublishAmqpTransferors;
use GhostUnicorns\CrtAmqp\Model\Status\ActivityCheckIsTimeToMakeAsTransferedDequeued;
use GhostUnicorns\CrtBase\Api\TransferorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use GhostUnicorns\CrtActivity\Api\ActivityRepositoryInterface;
use GhostUnicorns\CrtAmqp\Api\Data\TransferorInfoInterface;
use GhostUnicorns\CrtBase\Api\CrtListInterface;
use GhostUnicorns\CrtBase\Exception\CrtException;
use GhostUnicorns\CrtAmqpTransferor\Api\TransferorRepositoryInterface;

class TransferorConsumer
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
     * @var ActivityCheckIsTimeToMakeAsTransferedDequeued
     */
    private $activityCheckIsTimeToMakeAsTransferedDequeued;

    /**
     * @var TransferorRepositoryInterface
     */
    private $transferorRepository;

    /**
     * @var GenerateAndPublishAmqpTransferors
     */
    private $generateAndPublishAmqpTransferors;

    /**
     * @param CrtListInterface $crtList
     * @param ActivityRepositoryInterface $activityRepository
     * @param ActivityCheckIsTimeToMakeAsTransferedDequeued $activityCheckIsTimeToMakeAsTransferedDequeued
     * @param TransferorRepositoryInterface $transferorRepository
     * @param GenerateAndPublishAmqpTransferors $generateAndPublishAmqpTransferors
     */
    public function __construct(
        CrtListInterface $crtList,
        ActivityRepositoryInterface $activityRepository,
        ActivityCheckIsTimeToMakeAsTransferedDequeued $activityCheckIsTimeToMakeAsTransferedDequeued,
        TransferorRepositoryInterface $transferorRepository,
        GenerateAndPublishAmqpTransferors $generateAndPublishAmqpTransferors,
    ) {
        $this->crtList = $crtList;
        $this->activityRepository = $activityRepository;
        $this->activityCheckIsTimeToMakeAsTransferedDequeued = $activityCheckIsTimeToMakeAsTransferedDequeued;
        $this->transferorRepository = $transferorRepository;
        $this->generateAndPublishAmqpTransferors = $generateAndPublishAmqpTransferors;
    }

    /**
     * @param TransferorInfoInterface $transferorInfo
     * @throws NoSuchEntityException|CrtException
     */
    public function process(TransferorInfoInterface $transferorInfo): void
    {
        $activityId = $transferorInfo->getActivityId();
        $transferorType = $transferorInfo->getTransferorType();

        $activity = $this->activityRepository->getById($activityId);
        $activityType = $activity->getType();

        if ($activity->getStatus() !== ActivityStateInterface::REFINED &&
            $activity->getStatus() !== ActivityStateInterface::TRANSFERING) {
            throw new NoSuchEntityException(__(
                'Unable to run TransferorConsumer for activity id %1 - Activity status in wrong',
                $activityId
            ));
        }

        try {
            $this->transferorRepository->update(
                $activityId,
                $transferorType,
                AmqpStateInterface::IN_DEQUEUE
            );

            $transferorList = $this->crtList->getTransferorList($activityType);
            $transferors = $transferorList->getTransferors();

            if (!array_key_exists($transferorType, $transferors)) {
                throw new NoSuchEntityException(__(
                    'Unable to find Transferor with type %1',
                    $activityType
                ));
            }
            $transferor = $transferors[$transferorType];

            /** @var TransferorInterface $transferorModel */
            $transferorModel = $transferor['model'];

            $transferorModel->execute($activityId, $transferorType);

            $this->transferorRepository->update(
                $activityId,
                $transferorType,
                AmqpStateInterface::DEQUEUED
            );

            $this->generateAndPublishAmqpTransferors->execute($activityId);

            $this->activityCheckIsTimeToMakeAsTransferedDequeued->execute($activityId);
        } catch (\Exception $e) {
            $activity->setStatus(ActivityStateInterface::TRANSFER_ERROR);
            $activity->addExtraArray(
                [
                    'transferor_' . $transferorType => 'error',
                    'error' => $e->getMessage()
                ]
            );
            $this->activityRepository->save($activity);

            $this->transferorRepository->update(
                $activityId,
                $transferorType,
                AmqpStateInterface::DEQUEUED_ERROR
            );
            throw $e;
        }
    }
}
