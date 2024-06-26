<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback;

/**
 * Interface for classes which provide a status report entry.
 */
interface StatusProviderInterface
{
    /**
     * Returns the status of an extension or (sub)system
     *
     * @return Status[]
     */
    public function getStatus(): array;

    /**
     * Return label of this status
     */
    public function getLabel(): string;
}
