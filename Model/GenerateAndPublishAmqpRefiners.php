<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model;

use Exception;
use GhostUnicorns\CrtAmqp\Model\Publisher\RefinerPublisher;
use GhostUnicorns\CrtAmqpRefiner\Api\RefinerRepositoryInterface;
use GhostUnicorns\CrtEntity\Api\Data\EntityInterface;
use GhostUnicorns\CrtEntity\Api\EntityRepositoryInterface;

class GenerateAndPublishAmqpRefiners
{
    /**
     * @var RefinerRepositoryInterface
     */
    private $refinerRepository;

    /**
     * @var RefinerPublisher
     */
    private $refinerPublisher;

    /**
     * @var EntityRepositoryInterface
     */
    private $entityRepository;

    /**
     * @param RefinerRepositoryInterface $refinerRepository
     * @param RefinerPublisher $refinerPublisher
     * @param EntityRepositoryInterface $entityRepository
     */
    public function __construct(
        RefinerRepositoryInterface $refinerRepository,
        RefinerPublisher $refinerPublisher,
        EntityRepositoryInterface $entityRepository
    ) {
        $this->refinerRepository = $refinerRepository;
        $this->refinerPublisher = $refinerPublisher;
        $this->entityRepository = $entityRepository;
    }

    /**
     * @param int $activityId
     * @return void
     * @throws Exception
     */
    public function execute(int $activityId): void
    {
        $allActivityEntities = $this->entityRepository->getAllByActivityIdGroupedByIdentifier($activityId);

        /** @var EntityInterface[] $entities */
        foreach ($allActivityEntities as $entityIdentifier => $entities) {
            $this->refinerRepository->createOrUpdate(
                $activityId,
                $entityIdentifier,
                AmqpStateInterface::ADDED_TO_QUEUE
            );

            $this->refinerPublisher->execute($activityId, $entityIdentifier);
        }
    }
}
