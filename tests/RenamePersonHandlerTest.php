<?php
declare(strict_types=1);

namespace Triniti\Tests\People;

use Acme\Schemas\People\Command\RenamePersonV1;
use Acme\Schemas\People\Node\PersonV1;
use Gdbots\Schemas\Ncr\Mixin\NodeRenamed\NodeRenamed;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\People\RenamePersonHandler;
use Triniti\Schemas\People\Mixin\PersonRenamed\PersonRenamed;

final class RenamePersonHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand(): void
    {
        $node = PersonV1::create()->set('slug', 'original-slug-name');
        $this->ncr->putNode($node);
        $nodeRef = NodeRef::fromNode($node);

        $expectedId = $nodeRef->getId();

        $command = RenamePersonV1::create();
        $command->set('node_ref', $nodeRef);
        $command->set('new_slug', 'updated-slug-name');
        $command->set('old_slug', $node->get('slug'));

        $handler = new RenamePersonHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use ($expectedId) {
            $this->assertInstanceOf(NodeRenamed::class, $event);
            $this->assertSame(StreamId::fromString("person.history:{$expectedId}")->toString(), $streamId->toString());
            $this->assertSame('updated-slug-name', $event->get('new_slug'));
            $this->assertSame('original-slug-name', $event->get('old_slug'));
        });
    }

    public function testSlugNotChanged(): void
    {
        $node = PersonV1::create()->set('slug', 'original-slug-name');
        $this->ncr->putNode($node);
        $nodeRef = NodeRef::fromNode($node);

        $command = RenamePersonV1::create();
        $command->set('node_ref', $nodeRef);
        $command->set('new_slug', 'original-slug-name');
        $command->set('old_slug', $node->get('slug'));

        $handler = new RenamePersonHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $callbackIsCalled = false;
        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use (&$callbackIsCalled) {
            $callbackIsCalled = true;
        });
        $this->assertFalse($callbackIsCalled, 'Failed asserting that no event was created if old and new slugs are the same.');
    }
}
