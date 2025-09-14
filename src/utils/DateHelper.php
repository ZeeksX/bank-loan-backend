<?php
// File: src/Utils/DateHelper.php
namespace App\Utils;

class DateHelper {
    public static function now() {
        return time();
    }
    
    public static function toMongoDate($timestamp = null) {
        $timestamp = $timestamp ?: self::now();
        
        if (class_exists('MongoDB\BSON\UTCDateTime')) {
            return new \MongoDB\BSON\UTCDateTime($timestamp * 1000);
        }
        return $timestamp;
    }
    
    public static function fromMongoDate($mongoDate) {
        if ($mongoDate instanceof \MongoDB\BSON\UTCDateTime) {
            return $mongoDate->toDateTime()->getTimestamp();
        } else if (is_object($mongoDate) && isset($mongoDate->sec)) {
            return $mongoDate->sec;
        } else if (is_numeric($mongoDate)) {
            return $mongoDate;
        }
        return strtotime($mongoDate);
    }
    
    public static function format($mongoDate, $format = 'Y-m-d H:i:s') {
        return date($format, self::fromMongoDate($mongoDate));
    }
}
?>