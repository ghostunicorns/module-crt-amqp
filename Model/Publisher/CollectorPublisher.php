<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Publisher;

use Magento\Framework\MessageQueue\PublisherInterface;
use GhostUnicorns\CrtAmqp\Model\Data\CollectorInfoFactory;

class CollectorPublisher
{
    const TOPIC_NAME = 'crt.collector';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var CollectorInfoFactory
     */
    private $collectorInfoFactory;

    /**
     * @param PublisherInterface $publisher
     * @param CollectorInfoFactory $collectorInfoFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        CollectorInfoFactory $collectorInfoFactory
    ) {
        $this->publisher = $publisher;
        $this->collectorInfoFactory = $collectorInfoFactory;
    }

    /**
     * @param int $activityId
     * @param string $collectorType
     */
    public function execute(int $activityId, string $collectorType): void
    {
        $collectorInfo = $this->collectorInfoFactory->create(
            [
                'activity_id' => $activityId,
                'collector_type' => $collectorType
            ]
        );
        $this->publisher->publish(self::TOPIC_NAME, $collectorInfo);
    }
}
