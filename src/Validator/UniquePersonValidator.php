<?php
declare(strict_types=1);

namespace Triniti\People\Validator;

use Gdbots\Pbj\Assertion;
use Gdbots\Pbj\WellKnown\Identifier;
use Gdbots\Pbjx\DependencyInjection\PbjxValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Exception\RequestHandlingFailed;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Gdbots\Schemas\Pbjx\Mixin\Request\Request;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\People\Exception\PersonAlreadyExists;
use Triniti\Schemas\People\Mixin\GetPersonRequest\GetPersonRequestV1Mixin;
use Triniti\Schemas\People\Mixin\Person\PersonV1Mixin;

final class UniquePersonValidator implements EventSubscriber, PbjxValidator
{
    /**
     * @param PbjxEvent $pbjxEvent
     */
    public function validateCreatePerson(PbjxEvent $pbjxEvent): void
    {
        $command = $pbjxEvent->getMessage();

        Assertion::true($command->has('node'), 'Field "node" is required.', 'node');
        $node = $command->get('node');

        if ($node->has('slug')) {
            $this->ensureSlugIsAvailable($pbjxEvent, $node->get('slug'));
        }

        $this->ensureIdDoesNotExist($pbjxEvent, $node->get('_id'));
    }

    /**
     * @param PbjxEvent $pbjxEvent
     */
    public function validateRenamePerson(PbjxEvent $pbjxEvent): void
    {
        $command = $pbjxEvent->getMessage();

        Assertion::true($command->has('node_ref'), 'Field "node_ref" is required.', 'node_ref');
        Assertion::true($command->has('new_slug'), 'Field "new_slug" is required.', 'new_slug');

        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');

        /** @var Identifier $class */
        $class = PersonV1Mixin::findOne()->getField('_id')->getClassName();
        $id = $class::fromString($nodeRef->getId());

        $this->ensureSlugIsAvailable($pbjxEvent, $command->get('new_slug'), $id);
    }

    /**
     * @param PbjxEvent $pbjxEvent
     */
    public function validateUpdatePerson(PbjxEvent $pbjxEvent): void
    {
        $command = $pbjxEvent->getMessage();

        Assertion::true($command->has('new_node'), 'Field "new_node" is required.', 'new_node');
        $newNode = $command->get('new_node');

        /*
         * An update SHOULD NOT change the slug, so copy the slug from
         * the old node if it's present. To change the slug, use proper
         * "rename" command.
         */
        if ($command->has('old_node')) {
            $oldNode = $command->get('old_node');

            if ($oldNode->has('slug')) {
                $newNode->set('slug', $oldNode->get('slug'));
            }
        }
    }

    /**
     * @param PbjxEvent  $pbjxEvent
     * @param string     $slug
     * @param Identifier $id
     *
     * @throws PersonAlreadyExists
     */
    private function ensureSlugIsAvailable(PbjxEvent $pbjxEvent, string $slug, ?Identifier $id = null): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $message = $pbjxEvent->getMessage();

        try {
            $getPersonSchema = GetPersonRequestV1Mixin::findOne();
            $personSchema = PersonV1Mixin::findOne();
            /** @var Request $request */
            $request = $getPersonSchema->createMessage()
                ->set('consistent_read', true)
                ->set('qname', $personSchema->getQName()->toString())
                ->set('slug', $slug);

            $response = $pbjx->copyContext($message, $request)->request($request);
        } catch (RequestHandlingFailed $e) {
            if (Code::NOT_FOUND === $e->getResponse()->get('error_code')) {
                // this is what we want
                return;
            }

            throw $e;
        } catch (\Throwable $e) {
            throw $e;
        }

        if (null !== $id && $id->equals($response->get('node')->get('_id'))) {
            // this is the same person.
            return;
        }

        throw new PersonAlreadyExists(
            sprintf(
                'Person with slug [%s] already exists so [%s] cannot continue.',
                $slug,
                $message->generateMessageRef()
            )
        );
    }

    /**
     * @param PbjxEvent  $pbjxEvent
     * @param Identifier $id
     *
     * @throws PersonAlreadyExists
     */
    private function ensureIdDoesNotExist(PbjxEvent $pbjxEvent, Identifier $id): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $message = $pbjxEvent->getMessage();

        $streamId = StreamId::fromString("person.history:{$id->toString()}");
        $slice = $pbjx->getEventStore()->getStreamSlice($streamId, null, 1, true, true);

        if ($slice->count()) {
            throw new PersonAlreadyExists(
                sprintf(
                    'Person with id [%s] already exists so [%s] cannot continue.',
                    $id->toString(),
                    $message->generateMessageRef()
                )
            );
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'triniti:people:mixin:create-person.validate' => 'validateCreatePerson',
            'triniti:people:mixin:rename-person.validate' => 'validateRenamePerson',
            'triniti:people:mixin:update-person.validate' => 'validateUpdatePerson',
        ];
    }
}
