<?php
namespace macklus\backup\exceptions;

use yii\base\Exception;

class UnsuportedDatabaseException extends Exception
{

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'This database is not supported, please open a bug on github';
    }
}
