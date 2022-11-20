<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Status;

use GhostUnicorns\CrtAmqp\Model\AmqpActivityStateInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use GhostUnicorns\CrtActivity\Api\ActivityRepositoryInterface;

class ActivityMakeAsTransferorInDequeueing
{
    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    /**
     * @param ActivityRepositoryInterface $activityRepository
     */
    public function __construct(
        ActivityRepositoryInterface $activityRepository
    ) {
        $this->activityRepository = $activityRepository;
    }

    /**
     * @param int $activityId
     * @throws NoSuchEntityException
     */
    public function execute(int $activityId): void
    {
        $activity = $this->activityRepository->getById($activityId);
        if ($activity->getStatus() !== AmqpActivityStateInterface::TRANSFEROR_IN_DEQUEUING) {
            $activity->setStatus(AmqpActivityStateInterface::TRANSFEROR_IN_DEQUEUING);
            $this->activityRepository->save($activity);
        }
    }
}
