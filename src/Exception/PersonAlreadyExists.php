<?php
declare(strict_types=1);

namespace Triniti\People\Exception;

use Gdbots\Pbj\Exception\HasEndUserMessage;
use Gdbots\Schemas\Pbjx\Enum\Code;

final class PersonAlreadyExists extends \RuntimeException implements TrinitiPeopleException, HasEndUserMessage
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Person already exists.')
    {
        parent::__construct($message, Code::ALREADY_EXISTS);
    }

    /**
     * {@inheritdoc}
     */
    public function getEndUserMessage()
    {
        return $this->getMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function getEndUserHelpLink()
    {
        return null;
    }
}
