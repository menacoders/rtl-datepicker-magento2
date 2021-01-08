<?php
/**
 * Developer
 *
 * @author      Trong Le (trongithust@gmail.com)
 */

namespace Menacoders\Datepicker\Framework;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class Timezone extends \Magento\Framework\Stdlib\DateTime\Timezone
{
    const DEFAULT_LOCALE = 'en_US';
    const OVERRIDE_LOCALE = 'ar_SA';

    /**
     * @inheritdoc
     */
    public function getDateFormat($type = \IntlDateFormatter::SHORT)
    {
        return $this->getDateTimeFormater($type)->getPattern();
    }

    /**
     * @inheritdoc
     */
    public function getDateTimeFormater(
        $dateType = \IntlDateFormatter::SHORT,
        $timeType = \IntlDateFormatter::NONE,
        $timezone = null,
        $locale = null
    ) {
        $locale = $locale ? $locale : $this->_localeResolver->getLocale();
        if ($locale == self::OVERRIDE_LOCALE &&
            ($dateType == \IntlDateFormatter::SHORT || $dateType == \IntlDateFormatter::MEDIUM)) {
            $locale = self::DEFAULT_LOCALE;
        }

        return new \IntlDateFormatter(
            $locale,
            $dateType,
            $timeType,
            $timezone
        );
    }

    /**
     * @inheritdoc
     */
    public function date($date = null, $locale = null, $useTimezone = true, $includeTime = true)
    {
        $locale = $locale ?: $this->_localeResolver->getLocale();
        $timezone = $useTimezone
            ? $this->getConfigTimezone()
            : date_default_timezone_get();

        switch (true) {
            case (empty($date)):
                return new \DateTime('now', new \DateTimeZone($timezone));
            case ($date instanceof \DateTime):
                return $date->setTimezone(new \DateTimeZone($timezone));
            case ($date instanceof \DateTimeImmutable):
                return new \DateTime($date->format('Y-m-d H:i:s'), $date->getTimezone());
            case (!is_numeric($date)):
                $dateType = $includeTime ? \IntlDateFormatter::MEDIUM : \IntlDateFormatter::SHORT;
                $timeType = $includeTime ? \IntlDateFormatter::SHORT : \IntlDateFormatter::NONE;
                $formatter = $this->getDateTimeFormater($dateType, $timeType);

                $date = $this->appendTimeIfNeeded($date, $includeTime, $timezone, $locale);
                $date = $formatter->parse($date) ?: (new \DateTime($date))->getTimestamp();
                break;
        }

        return (new \DateTime(null, new \DateTimeZone($timezone)))->setTimestamp($date);
    }

    /**
     * Append time to DateTime
     *
     * @param string $date
     * @param boolean $includeTime
     * @param string $timezone
     * @param string $locale
     * @return string
     * @throws LocalizedException
     */
    private function appendTimeIfNeeded($date, $includeTime, $timezone, $locale)
    {
        if ($includeTime && !preg_match('/\d{1}:\d{2}/', $date)) {

            $formatterWithoutHour = $this->getDateTimeFormater(
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::NONE
            );
            $convertedDate = $formatterWithoutHour->parse($date);

            if (!$convertedDate) {
                throw new LocalizedException(
                    new Phrase(
                        'Could not append time to DateTime'
                    )
                );

            }

            $formatterWithHour = $this->getDateTimeFormater(
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::SHORT,
                $timezone,
                $locale
            );

            $date = $formatterWithHour->format($convertedDate);
        }
        return $date;
    }
}
