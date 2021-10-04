<?php

namespace Sunnysideup\FlushFrontEnd\Model;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\ReadonlyField;
use Sunnysideup\FlushFrontEnd\Control\FlushReceiver;
use SilverStripe\Control\Controller;

use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Flushable;

class FlushRecord extends DataObject implements Flushable
{

    private static $table_name = 'FlushRecord';

    private static $db = [
        'FlushCode' => 'Varchar',
        'Done' => 'Boolean',
    ];

    private static $summary_fields = [
        'Created.Nice' => 'When',
        'FlushCodee' => 'Code',
    ];

    private static $indexes = [
        'Created' => true,
    ];

    private static $default_sort = [
        'Created' => 'DESC',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.Main',
            [
                ReadonlyField::create(
                    'Created',
                    'When'
                ),
                ReadonlyField::create(
                    'FlushCode',
                    'Code'
                ),
                CheckboxField::create(
                    'Done',
                    'Done'
                )
            ]
        );
        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $hex = bin2hex(random_bytes(18));
        $code = serialize($hex);
        $code = preg_replace("/[^a-zA-Z0-9]+/", "", $code);
        $this->Code = $code;
    }

    protected static $done = false;

    public static function flush()
    {
        if(Director::is_cli() && self::$done === false)  {
            self::$done = true;
            register_shutdown_function(function () {
                $obj = \Sunnysideup\FlushFrontEnd\Model\FlushRecord::create();
                $obj->write();
                $code = $obj->Code;
                $url = Director::absoluteURL(
                    Controller::join_links(FlushReceiver::my_url_segment(),  $code)
                );
                DB::alteration_message('Creating flush link: '.$url);
                \Sunnysideup\FlushFrontEnd\Model\FlushRecord::run_flush($url);
            });
        }
    }

    public static function run_flush($url)
    {
        // Create a new cURL resource
        $ch = curl_init();

        // Set the file URL to fetch through cURL
        curl_setopt($ch, CURLOPT_URL, $url);

        // Do not check the SSL certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Return the actual result of the curl result instead of success code
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

}
