<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Publisher;

use Magento\Framework\MessageQueue\PublisherInterface;
use GhostUnicorns\CrtAmqp\Model\Data\RefinerInfoFactory;

class RefinerPublisher
{
    const TOPIC_NAME = 'crt.refiner';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var RefinerInfoFactory
     */
    private $refinerInfoFactory;

    /**
     * @param PublisherInterface $publisher
     * @param RefinerInfoFactory $refinerInfoFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        RefinerInfoFactory $refinerInfoFactory
    ) {
        $this->publisher = $publisher;
        $this->refinerInfoFactory = $refinerInfoFactory;
    }

    /**
     * @param int $activityId
     * @param string $entityIdentifier
     */
    public function execute(int $activityId, string $entityIdentifier): void
    {
        $refinerInfo = $this->refinerInfoFactory->create(
            [
                'activity_id' => $activityId,
                'entity_identifier' => $entityIdentifier
            ]
        );
        $this->publisher->publish(self::TOPIC_NAME, $refinerInfo);
    }
}
