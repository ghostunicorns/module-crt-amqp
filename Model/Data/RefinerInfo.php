<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Data;

use GhostUnicorns\CrtAmqp\Api\Data\RefinerInfoInterface;

class RefinerInfo implements RefinerInfoInterface
{
    /**
     * @var int
     */
    private $activity_id;

    /**
     * @var string
     */
    private $entity_identifier;

    /**
     * @param int $activity_id
     * @param string $entity_identifier
     */
    public function __construct(
        int $activity_id,
        string $entity_identifier
    ) {
        $this->activity_id = $activity_id;
        $this->entity_identifier = $entity_identifier;
    }

    /**
     * @return int
     */
    public function getActivityId(): int
    {
        return $this->activity_id;
    }

    /**
     * @return string
     */
    public function getEntityIdentifier(): string
    {
        return $this->entity_identifier;
    }
}
