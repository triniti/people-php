<?php
declare(strict_types=1);

namespace Triniti\Tests\People;

use Acme\Schemas\People\Command\DeletePersonV1;
use Acme\Schemas\People\Node\PersonV1;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\People\DeletePersonHandler;
use Triniti\Schemas\People\Mixin\PersonDeleted\PersonDeleted;

final class DeletePersonHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand(): void
    {
        $node = PersonV1::create()->set('slug', 'original-slug-name');
        $nodeRef = NodeRef::fromNode($node);
        $this->ncr->putNode($node);

        $expectedId = $nodeRef->getId();

        $command = DeletePersonV1::create();
        $command->set('node_ref', $nodeRef);

        $handler = new DeletePersonHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use ($expectedId) {
            $this->assertInstanceOf(PersonDeleted::class, $event);
            $this->assertSame(StreamId::fromString("person.history:{$expectedId}")->toString(), $streamId->toString());
            $this->assertSame('original-slug-name', $event->get('slug'));
        });
    }
}
