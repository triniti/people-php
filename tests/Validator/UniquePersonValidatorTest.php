<?php
declare(strict_types=1);

namespace Triniti\Tests\People\Validator;

use Acme\Schemas\People\Command\CreatePersonV1;
use Acme\Schemas\People\Command\RenamePersonV1;
use Acme\Schemas\People\Command\UpdatePersonV1;
use Acme\Schemas\People\Event\PersonCreatedV1;
use Acme\Schemas\People\Event\PersonUpdatedV1;
use Acme\Schemas\People\Node\PersonV1;
use Acme\Schemas\People\Request\GetPersonRequestV1;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\People\GetPersonRequestHandler;
use Triniti\People\Validator\UniquePersonValidator;
use Triniti\Tests\People\AbstractPbjxTest;

final class UniquePersonValidatorTest extends AbstractPbjxTest
{
    public function setup()
    {
        parent::setup();

        // prepare request handlers that this test case requires
        PbjxEvent::setPbjx($this->pbjx);
        $this->locator->registerRequestHandler(
            GetPersonRequestV1::schema()->getCurie(),
            new GetPersonRequestHandler($this->ncr)
        );
    }

    public function testValidateCreatePersonThatDoesNotExist(): void
    {
        $command = CreatePersonV1::create();
        $node = PersonV1::create();
        $command->set('node', $node);

        $validator = new UniquePersonValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreatePerson($pbjxEvent);

        // if it gets here it's a pass
        $this->assertTrue(true);
    }

    /**
     * @expectedException \Triniti\People\Exception\PersonAlreadyExists
     */
    public function testValidateCreatePersonThatDoesExistBySlug(): void
    {
        $command = CreatePersonV1::create();
        $existingNode = PersonV1::fromArray(['slug' => 'existing-person-slug']);
        $newNode = PersonV1::fromArray(['slug' => 'existing-person-slug']);
        $this->ncr->putNode($existingNode);
        $command->set('node', $newNode);

        $validator = new UniquePersonValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreatePerson($pbjxEvent);
    }

    /**
     * @expectedException \Triniti\People\Exception\PersonAlreadyExists
     */
    public function testValidateCreatePersonThatDoesExistById(): void
    {
        $command = CreatePersonV1::create();
        $event = PersonCreatedV1::create();
        $node = PersonV1::create();
        $command->set('node', $node);
        $event->set('node', $node);
        $this->eventStore->putEvents(StreamId::fromString("person.history:{$node->get('_id')}"), [$event]);

        $validator = new UniquePersonValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreatePerson($pbjxEvent);
    }

    /**
     * @expectedException \Gdbots\Pbj\Exception\FieldNotDefined
     */
    public function testValidateUpdatePersonFailsWithoutANewNode(): void
    {
        $command = UpdatePersonV1::create();
        $event = PersonUpdatedV1::create();
        $node = PersonV1::create();
        $event->set('node', $node);
        $this->eventStore->putEvents(StreamId::fromString("person.history:{$node->get('_id')}"), [$event]);

        $validator = new UniquePersonValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateUpdatePerson($pbjxEvent);
    }

    public function testValidateUpdatePersonSlugIsCopied(): void
    {
        $oldPage = PersonV1::create()->set('slug', 'first-person');
        $newPage = PersonV1::create()->set('slug', 'first-updated-person');
        $command = UpdatePersonV1::create()
            ->set('old_node', $oldPage)
            ->set('new_node', $newPage);
        $pbjxEvent = new PbjxEvent($command);

        $validator = new UniquePersonValidator();
        $validator->validateUpdatePerson($pbjxEvent);
        $this->assertSame('first-person', $command->get('new_node')->get('slug'));
    }

    public function testValidateRenamePerson(): void
    {
        $person = PersonV1::create();
        $command = RenamePersonV1::create()
            ->set('node_ref', NodeRef::fromNode($person))
            ->set('new_slug', 'new-slug-for-person');

        $pbjxEvent = new PbjxEvent($command);
        $validator = new UniquePersonValidator();
        $validator->validateRenamePerson($pbjxEvent);

        // if it gets here then it's a pass
        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Pbj\Exception\AssertionFailed
     */
    public function testValidateRenamePersonWithoutNodeRef(): void
    {
        $command = RenamePersonV1::create();
        $pbjxEvent = new PbjxEvent($command);
        $validator = new UniquePersonValidator();
        $validator->validateRenamePerson($pbjxEvent);
    }

    /**
     * @expectedException \Gdbots\Pbj\Exception\AssertionFailed
     */
    public function testValidateRenamePersonWithoutNewSlug(): void
    {
        $person = PersonV1::create();
        $command = RenamePersonV1::create()
            ->set('node_ref', NodeRef::fromNode($person));

        $pbjxEvent = new PbjxEvent($command);
        $validator = new UniquePersonValidator();
        $validator->validateRenamePerson($pbjxEvent);
    }
}
