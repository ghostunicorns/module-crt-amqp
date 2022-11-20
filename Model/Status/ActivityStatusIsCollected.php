<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Status;

use GhostUnicorns\CrtActivity\Model\ActivityStateInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use GhostUnicorns\CrtActivity\Api\ActivityRepositoryInterface;

class ActivityStatusIsCollected
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
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute(int $activityId): bool
    {
        $activity = $this->activityRepository->getById($activityId);
        return $activity->getStatus() === ActivityStateInterface::COLLECTED;
    }
}
