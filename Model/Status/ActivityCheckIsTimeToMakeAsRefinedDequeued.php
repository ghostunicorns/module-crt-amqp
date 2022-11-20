<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Status;

use GhostUnicorns\CrtActivity\Model\ActivityStateInterface;
use GhostUnicorns\CrtAmqp\Model\AmqpStateInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use GhostUnicorns\CrtActivity\Api\ActivityRepositoryInterface;
use GhostUnicorns\CrtAmqpRefiner\Api\RefinerRepositoryInterface;

class ActivityCheckIsTimeToMakeAsRefinedDequeued
{
    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    /**
     * @var RefinerRepositoryInterface
     */
    private $refinerRepository;

    /**
     * @param ActivityRepositoryInterface $activityRepository
     * @param RefinerRepositoryInterface $refinerRepository
     */
    public function __construct(
        ActivityRepositoryInterface $activityRepository,
        RefinerRepositoryInterface $refinerRepository
    ) {
        $this->activityRepository = $activityRepository;
        $this->refinerRepository = $refinerRepository;
    }

    /**
     * @param int $activityId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute(int $activityId): bool
    {
        $refiners = $this->refinerRepository->getAllByActivityId($activityId);

        if (!count($refiners)) {
            return false;
        }

        foreach ($refiners as $refiner) {
            if ($refiner->getStatus() !== AmqpStateInterface::DEQUEUED) {
                return false;
            }
        }

        $activity = $this->activityRepository->getById($activityId);
        $activity->setStatus(ActivityStateInterface::REFINED);
        $this->activityRepository->save($activity);

        return true;
    }
}
