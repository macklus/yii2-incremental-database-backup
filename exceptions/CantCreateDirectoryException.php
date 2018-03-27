<?php
namespace macklus\backup\exceptions;

use yii\base\Exception;

class CantCreateDirectoryException extends Exception
{

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Can not create directory.';
    }
}
