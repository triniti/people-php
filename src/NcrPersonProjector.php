<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbjx\DependencyInjection\PbjxProjector;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Triniti\Schemas\People\Mixin\PersonCreated\PersonCreated;
use Triniti\Schemas\People\Mixin\PersonDeleted\PersonDeleted;
use Triniti\Schemas\People\Mixin\PersonRenamed\PersonRenamed;
use Triniti\Schemas\People\Mixin\PersonUpdated\PersonUpdated;

class NcrPersonProjector implements PbjxProjector
{
    use EventSubscriberTrait;

    /** @var Ncr */
    protected $ncr;

    /** @var NcrSearch */
    protected $ncrSearch;

    /**
     * @param Ncr       $ncr
     * @param NcrSearch $ncrSearch
     */
    public function __construct(Ncr $ncr, NcrSearch $ncrSearch)
    {
        $this->ncr = $ncr;
        $this->ncrSearch = $ncrSearch;
    }

    /**
     * @param PersonCreated $event
     */
    public function onPersonCreated(PersonCreated $event): void
    {
        $node = $event->get('node');
        $this->ncr->putNode($node);
        if (!$event->isReplay()) {
            $this->ncrSearch->indexNodes([$node]);
        }
    }

    /**
     * @param PersonDeleted $event
     */
    public function onPersonDeleted(PersonDeleted $event): void
    {
        $node = $this->ncr->getNode($event->get('node_ref'), true);
        $node->set('status', NodeStatus::DELETED());
        $this->putNode($node, $event);
    }

    /**
     * @param PersonRenamed $event
     */
    public function onPersonRenamed(PersonRenamed $event): void
    {
        $node = $this->ncr->getNode($event->get('node_ref'), true);
        $node->set('slug', $event->get('new_slug'));
        $this->putNode($node, $event);
    }

    /**
     * @param PersonUpdated $event
     */
    public function onPersonUpdated(PersonUpdated $event): void
    {
        $newNode = $event->get('new_node');
        $expectedEtag = $event->isReplay() ? null : $event->get('old_etag');
        $this->ncr->putNode($newNode, $expectedEtag);
        if (!$event->isReplay()) {
            $this->ncrSearch->indexNodes([$newNode]);
        }
    }

    /**
     * @param Node  $node
     * @param Event $event
     */
    protected function putNode(Node $node, Event $event): void
    {
        $expectedEtag = $node->get('etag');
        $node
            ->set('updated_at', $event->get('occurred_at'))
            ->set('updater_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef())
            ->set('etag', $node->generateEtag(['etag', 'updated_at']));

        $this->ncr->putNode($node, $expectedEtag);

        if ($event->isReplay()) {
            // on replay we don't want to reindex, we generally do that
            // as a separate task, in batches, using console ncr:reindex-nodes
            return;
        }

        $this->ncrSearch->indexNodes([$node]);
    }
}
