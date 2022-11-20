<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model;

interface AmqpActivityStateInterface
{
    /* @todo da elininare in favore di ActivityStateInterface:: */
    const COLLECTOR_ADDED_TO_QUEUE = 'collector_added_to_queue';
    const COLLECTOR_ADDED_TO_QUEUE_ERROR = 'collector_added_to_queue_error';
    const COLLECTOR_IN_DEQUEUING = 'collector_in_dequeuing';

    const REFINER_ADDED_TO_QUEUE = 'refiner_added_to_queue';
    const REFINER_ADDED_TO_QUEUE_ERROR = 'refiner_added_to_queue_error';
    const REFINER_IN_DEQUEUING = 'refiner_in_dequeuing';

    const TRANSFEROR_ADDED_TO_QUEUE = 'transferor_added_to_queue';
    const TRANSFEROR_ADDED_TO_QUEUE_ERROR = 'transferor_added_to_queue_error';
    const TRANSFEROR_IN_DEQUEUING = 'transferor_in_dequeuing';
}
