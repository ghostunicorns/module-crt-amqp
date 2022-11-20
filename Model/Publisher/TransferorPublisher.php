<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Publisher;

use Magento\Framework\MessageQueue\PublisherInterface;
use GhostUnicorns\CrtAmqp\Model\Data\TransferorInfoFactory;

class TransferorPublisher
{
    const TOPIC_NAME = 'crt.transferor';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var TransferorInfoFactory
     */
    private $transferorInfoFactory;

    /**
     * @param PublisherInterface $publisher
     * @param TransferorInfoFactory $transferorInfoFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        TransferorInfoFactory $transferorInfoFactory
    ) {
        $this->publisher = $publisher;
        $this->transferorInfoFactory = $transferorInfoFactory;
    }

    /**
     * @param int $activityId
     * @param string $transferorType
     */
    public function execute(int $activityId, string $transferorType): void
    {
        $transferorInfo = $this->transferorInfoFactory->create(
            [
                'activity_id' => $activityId,
                'transferor_type' => $transferorType
            ]
        );
        $this->publisher->publish(self::TOPIC_NAME, $transferorInfo);
    }
}
