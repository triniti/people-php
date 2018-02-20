<?php
declare(strict_types=1);

namespace Triniti\Tests\People;

use Acme\Schemas\People\Event\PersonCreatedV1;
use Acme\Schemas\People\Event\PersonDeletedV1;
use Acme\Schemas\People\Event\PersonRenamedV1;
use Acme\Schemas\People\Event\PersonUpdatedV1;
use Acme\Schemas\People\Node\PersonV1;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\NodeRef;
use Triniti\People\NcrPersonProjector;


final class NcrPersonProjectorTest extends AbstractPbjxTest
{
    /** @var NcrPersonProjector */
    protected $projector;

    /** @var NcrSearch|\PHPUnit_Framework_MockObject_MockObject */
    protected $ncrSearch;

    public function setup()
    {
        parent::setup();
        $this->ncrSearch = $this->getMockBuilder(MockNcrSearch::class)->getMock();
        $this->projector = new NcrPersonProjector($this->ncr, $this->ncrSearch);
    }

    public function testOnPersonCreated(): void
    {
        $person = PersonV1::create();
        $nodeRef = NodeRef::fromNode($person);
        $event = PersonCreatedV1::create()->set('node', $person);

        $this->ncrSearch->expects($this->once())->method('indexNodes');
        $this->projector->onPersonCreated($event);
        $actualPerson = $this->ncr->getNode($nodeRef);

        $this->assertTrue($person->equals($actualPerson));
    }

    public function testOnPersonCreatedIsReplay(): void
    {
        $person = PersonV1::create();
        $nodeRef = NodeRef::fromNode($person);
        $event = PersonCreatedV1::create()->set('node', $person);
        $event->isReplay(true);

        $this->ncrSearch->expects($this->never())->method('indexNodes');
        $this->projector->onPersonCreated($event);
        $actualPerson = $this->ncr->getNode($nodeRef);

        $this->assertTrue($person->equals($actualPerson));
    }

    public function testOnPersonUpdated(): void
    {
        $oldPerson = PersonV1::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db']);
        $nodeRef = NodeRef::fromNode($oldPerson);
        $this->ncr->putNode($oldPerson);

        $newPerson = PersonV1::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db']);
        $newPerson
            ->set('title', 'New person')
            ->set('etag', $newPerson->generateEtag(['etag', 'updated_at']));

        $event = PersonUpdatedV1::create()
            ->set('old_node', $oldPerson)
            ->set('new_node', $newPerson)
            ->set('old_etag', $oldPerson->get('etag'))
            ->set('new_etag', $newPerson->get('etag'))
            ->set('node_ref', $nodeRef);

        $this->ncrSearch->expects($this->once())->method('indexNodes');
        $this->projector->onPersonUpdated($event);
        $actualPerson = $this->ncr->getNode($nodeRef);

        $this->assertTrue($newPerson->equals($actualPerson));
    }

    public function testOnPersonUpdatedIsReplay(): void
    {
        $oldPerson = PersonV1::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db']);
        $oldPerson->set('title', 'Old person');
        $nodeRef = NodeRef::fromNode($oldPerson);
        $this->ncr->putNode($oldPerson);

        $newPerson = PersonV1::fromArray(['_id', '7afcc2f1-9654-46d1-8fc1-b0511df257db']);
        $newPerson
            ->set('title', 'New person')
            ->set('etag', $newPerson->generateEtag(['etag', 'updated_at']));

        $event = PersonUpdatedV1::create()
            ->set('old_node', $oldPerson)
            ->set('new_node', $newPerson)
            ->set('old_etag', $oldPerson->get('etag'))
            ->set('new_etag', $newPerson->get('etag'))
            ->set('node_ref', $nodeRef);
        $event->isReplay(true);

        $this->ncrSearch->expects($this->never())->method('indexNodes');
        $this->projector->onPersonUpdated($event);
        $actualPerson = $this->ncr->getNode($nodeRef);

        $this->assertTrue($actualPerson->equals($oldPerson));
    }

    public function testOnPersonDeleted(): void
    {
        $person = PersonV1::create();
        $nodeRef = NodeRef::fromNode($person);
        $this->ncr->putNode($person);

        $event = PersonDeletedV1::create()->set('node_ref', $nodeRef);

        $this->projector->onPersonDeleted($event);
        $deletedPerson = $this->ncr->getNode($nodeRef);
        $this->assertEquals(NodeStatus::DELETED(), $deletedPerson->get('status'));
    }

    public function testOnPersonRenamed(): void
    {
        $person = PersonV1::fromArray(['slug' => 'person-to-rename']);
        $nodeRef = NodeRef::fromNode($person);
        $this->ncr->putNode($person);

        $event = PersonRenamedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('new_slug', 'new-person-name');

        $this->projector->onPersonRenamed($event);
        $renamedPerson = $this->ncr->getNode($nodeRef);
        $this->assertEquals('new-person-name', $renamedPerson->get('slug'));
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testOnPersonDeletedNodeRefNotExists(): void
    {
        $event = PersonDeletedV1::create()
            ->set('node_ref', NodeRef::fromString('acme:person:7afcc2f1-9654-46d1-8fc1-b0511df257db'));

        $this->projector->onPersonDeleted($event);
    }
}
