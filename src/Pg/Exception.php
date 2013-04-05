<?php
namespace Pg;

class Exception extends \Exception
{
    public $pgCode;
    public function __construct($msg, $code = 0, $previous = null, $pgCode = '') {
        parent::__construct($msg, $code, $previous);
        $this->pgCode = $pgCode;
    }
}
