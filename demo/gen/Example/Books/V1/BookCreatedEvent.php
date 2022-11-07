<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: books.proto

namespace Example\Books\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 *  option (quarks_tech.protoevent.v1.enabled) = true;
 *
 * Generated from protobuf message <code>example.books.v1.BookCreatedEvent</code>
 */
class BookCreatedEvent extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>int32 id = 1[json_name = "id"];</code>
     */
    protected $id = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $id
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Books::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>int32 id = 1[json_name = "id"];</code>
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Generated from protobuf field <code>int32 id = 1[json_name = "id"];</code>
     * @param int $var
     * @return $this
     */
    public function setId($var)
    {
        GPBUtil::checkInt32($var);
        $this->id = $var;

        return $this;
    }

}

