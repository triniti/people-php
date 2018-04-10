<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Triniti\Schemas\People\Mixin\Person\Person;

trait PbjxHelperTrait
{
    /**
     * @param Node $node
     *
     * @return bool
     */
    protected function isNodeSupported(Node $node): bool
    {
        return $node instanceof Person;
    }
}
