<?php

use EventEspresso\core\domain\values\currency\Money;
use EventEspresso\core\services\currency\formatters\CurrencyAmountFormatterInterface;
use EventEspresso\core\services\currency\MoneyFactory;
use EventEspresso\core\services\currency\formatters\MoneyFormatter;
use EventEspresso\core\services\loaders\LoaderFactory;

defined('EVENT_ESPRESSO_VERSION') || exit;

/**
 * EE_Money_Field
 * Model field for dealing with money amounts. Originally this accepted and returns float
 * values, but now it also deals in Money entities.
 */
class EE_Money_Field extends EE_Float_Field
{

    /**
     * @var $money_factory MoneyFactory
     */
    protected $money_factory;

    /**
     * @var $money_formatter MoneyFormatter
     */
    protected $money_formatter;


    /**
     * @param string       $table_column
     * @param string       $nicename
     * @param bool         $nullable
     * @param null         $default_value
     * @param MoneyFactory $factory
     * @param MoneyFormatter $money_formatter
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     */
    public function __construct(
        $table_column,
        $nicename,
        $nullable,
        $default_value = null,
        MoneyFactory $factory = null,
        MoneyFormatter $money_formatter = null
    ) {
        if (! $factory instanceof MoneyFactory) {
            $factory = LoaderFactory::getLoader()->getShared('EventEspresso\core\services\currency\MoneyFactory');
        }
        $this->money_factory = $factory;
        if (! $money_formatter instanceof MoneyFormatter) {
            $money_formatter = LoaderFactory::getLoader()->getShared('EventEspresso\core\services\currency\formatters\MoneyFormatter');
        }
        $this->money_formatter = $money_formatter;
        parent::__construct($table_column, $nicename, $nullable, $default_value);
        $this->setSchemaType('object');
    }



    /**
     * Formats the value for pretty output, according to $schema.
     * If legacy filters are being used, uses EEH_Template::format_currency() to format it;
     * otherwise uses MoneyFormatter.
     * Schemas:
     *    CurrencyAmountFormatterInterface::RAW: "3023.0000"
     *    CurrencyAmountFormatterInterface::DECIMAL_ONLY: "3023.00"
     *    CurrencyAmountFormatterInterface::ADD THOUSANDS/'localized_float': "3,023.00"
     *    CurrencyAmountFormatterInterface::ADD_CURRENCY_SIGN/'no_currency_code': "$3,023.00"
     *    CurrencyAmountFormatterInterface::ADD_CURRENCY_CODE/null: "$3,023.00<span>USD</span>"
     *
     * @param string|Money $value_on_field_to_be_outputted
     * @param string       $schema
     * @return string
     * @throws \EE_Error
     */
    public function prepare_for_pretty_echoing($value_on_field_to_be_outputted, $schema = null)
    {
        //has someone hooked into the old currency formatter helper's filters?
        //if so, we had better stick with it
        if (apply_filters(
            'FHEE__EE_Money_Field__prpeare_for_pretty_echoing',
            has_filter('FHEE__EEH_Template__format_currency__raw_amount')
            || has_filter('FHEE__EEH_Template__format_currency__CNT_ISO')
            || has_filter('FHEE__EEH_Template__format_currency__amount')
            || has_filter('FHEE__EEH_Template__format_currency__display_code')
            || has_filter('FHEE__EEH_Template__format_currency__amount_formatted'),
            $this,
            $value_on_field_to_be_outputted,
            $schema
        )) {
            $pretty_float = parent::prepare_for_pretty_echoing($value_on_field_to_be_outputted);

            if ($schema === 'localized_float') {
                return $pretty_float;
            }
            $display_code = true;
            if ($schema === 'no_currency_code') {
                //			echo "schema no currency!";
                $display_code = false;
            }

            //we don't use the $pretty_float because format_currency will take care of it.
            return EEH_Template::format_currency($value_on_field_to_be_outputted, false, $display_code);
        }
        //ok let's just use the new formatting code then
        $schema = (string)$schema;
        switch ($schema) {
            case (string)CurrencyAmountFormatterInterface::ADD_CURRENCY_CODE:
                $formatting_level = CurrencyAmountFormatterInterface::ADD_CURRENCY_CODE;
                break;
            case 'no_currency_code':
            case (string)CurrencyAmountFormatterInterface::ADD_CURRENCY_SIGN:
                $formatting_level = CurrencyAmountFormatterInterface::ADD_CURRENCY_SIGN;
                break;
            case (string)CurrencyAmountFormatterInterface::ADD_THOUSANDS:
            case 'localized_float':
                $formatting_level = CurrencyAmountFormatterInterface::ADD_THOUSANDS;
                break;
            case (string)CurrencyAmountFormatterInterface::DECIMAL_ONLY:
                $formatting_level = CurrencyAmountFormatterInterface::DECIMAL_ONLY;
                break;
            default:
                $formatting_level = CurrencyAmountFormatterInterface::INTERNATIONAL;
        }
        $value_on_field_to_be_outputted = $this->ensureMoney($value_on_field_to_be_outputted);
        return $this->money_formatter->format(
            $value_on_field_to_be_outputted->amount(),
            $value_on_field_to_be_outputted->currency(),
            $formatting_level
        );
    }



    /**
     * Make sure this value is a money object
     *
     * @param string|float|int|Money $value
     * @return Money
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EE_Error
     */
    private function ensureMoney($value)
    {
        if (! $value instanceof Money) {
            return $this->money_factory->createForSite($value);
        }
        return $value;
    }



    /**
     * Ensures we're dealing with something that isn't Money
     * (for passing off to legacy systems or the parent field)
     * @param  string|float|int|Money $value
     * @return string|float|int
     */
    private function ensureNotMoney($value)
    {
        if( $value instanceof Money) {
            return $value->amount();
        }
        return $value;
    }

    /**
     * If provided with a string, strips out money-related formatting to turn it into a proper float.
     * Rounds the float to the correct number of decimal places for this country's currency.
     * Also, interprets periods and commas according to the country's currency settings.
     * So if you want to pass in a string that NEEDS to interpret periods as decimal marks, call floatval() on it first.
     *
     * @param string|float|int|Money $value_inputted_for_field_on_model_object
     * @return Money
     */
    public function prepare_for_set($value_inputted_for_field_on_model_object)
    {
        if ($value_inputted_for_field_on_model_object instanceof Money) {
            return $value_inputted_for_field_on_model_object;
        }
        //now it's a float-style string or number
        return $this->ensureMoney(
            parent::prepare_for_set($value_inputted_for_field_on_model_object)
        );
    }



    /**
     * @param string|float|int|Money $value_of_field_on_model_object
     * @return float
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     */
    public function prepare_for_get($value_of_field_on_model_object)
    {
        $value_of_field_on_model_object = $this->ensureNotMoney($value_of_field_on_model_object);
        $c = EE_Registry::instance()->CFG->currency;
        return round(parent::prepare_for_get($value_of_field_on_model_object), $c->dec_plc);
    }


    /**
     * Takes the incoming float and create a money entity for the model object
     *
     * @param string|float|int $value_found_in_db_for_model_object
     * @return Money
     * @throws \EventEspresso\core\exceptions\InvalidIdentifierException
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EE_Error
     */
    public function prepare_for_set_from_db($value_found_in_db_for_model_object)
    {
        return $this->money_factory->createForSite($value_found_in_db_for_model_object);
    }



    /**
     * Prepares a value for use in the DB
     * @param string|float|int|Money $value_of_field_on_model_object
     * @return float
     */
    public function prepare_for_use_in_db($value_of_field_on_model_object)
    {
        $value_of_field_on_model_object = $this->ensureNotMoney($value_of_field_on_model_object);
        return parent::prepare_for_use_in_db($value_of_field_on_model_object);
    }



    public function getSchemaProperties()
    {
        return array(
            'raw' => array(
                'description' =>  sprintf(
                    __('%s - the raw value as it exists in the database as a simple float.', 'event_espresso'),
                    $this->get_nicename()
                ),
                'type' => 'number'
            ),
            'pretty' => array(
                'description' =>  sprintf(
                    __('%s - formatted for display in the set currency and decimal places.', 'event_espresso'),
                    $this->get_nicename()
                ),
                'type' => 'string'
            )
        );
    }
}