<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractNodeProjector;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Mixin\NodeCreated\NodeCreated;
use Gdbots\Schemas\Ncr\Mixin\NodeDeleted\NodeDeleted;
use Gdbots\Schemas\Ncr\Mixin\NodeRenamed\NodeRenamed;
use Gdbots\Schemas\Ncr\Mixin\NodeUpdated\NodeUpdated;
use Triniti\Schemas\People\Mixin\Person\PersonV1Mixin;

class NcrPersonProjector extends AbstractNodeProjector implements EventSubscriber
{
    use EventSubscriberTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $curie = PersonV1Mixin::findOne()->getCurie();
        return [
            "{$curie->getVendor()}:{$curie->getPackage()}:event:*" => 'onEvent',
        ];
    }

    /**
     * @param NodeCreated $event
     * @param Pbjx        $pbjx
     */
    public function onPersonCreated(NodeCreated $event, Pbjx $pbjx): void
    {
        $this->handleNodeCreated($event, $pbjx);
    }

    /**
     * @param NodeDeleted $event
     * @param Pbjx        $pbjx
     */
    public function onPersonDeleted(NodeDeleted $event, Pbjx $pbjx): void
    {
        $this->handleNodeDeleted($event, $pbjx);
    }

    /**
     * @param NodeRenamed $event
     * @param Pbjx        $pbjx
     */
    public function onPersonRenamed(NodeRenamed $event, Pbjx $pbjx): void
    {
        $this->handleNodeRenamed($event, $pbjx);
    }

    /**
     * @param NodeUpdated $event
     * @param Pbjx        $pbjx
     */
    public function onPersonUpdated(NodeUpdated $event, Pbjx $pbjx): void
    {
        $this->handleNodeUpdated($event, $pbjx);
    }
}
