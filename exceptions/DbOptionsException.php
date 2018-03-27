<?php
namespace macklus\backup\exceptions;

use yii\base\Exception;

class DbOptionsException extends Exception
{

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Database object has not $dsn property configured.';
    }
}
