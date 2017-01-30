<?php
/**
 * @package	HikaShop Dimensions and Weight View Plugin for Joomla!
 * @version	1.0
 * @author	Igor Inkovskiy
 * @authorUrl http://igor-i.tmweb.ru
 * @copyright	(C) 2017 Igor Inkovskiy
 * @license	Beerware
 */
defined('_JEXEC') or die('Restricted access');
class plgHikashopDimensions_and_weight_view extends JPlugin {


    /**
     * plgHikashopDimensions_and_weight_view constructor
     * @param object $subject
     * @param array $config
     * @since
     */
    function __construct(&$subject,$config) {
        parent::__construct($subject,$config);
        if (!isset($this->params)) {
            $plugin = JPluginHelper::getPlugin('hikashop','dimensions_and_weight_view');
            if (version_compare(JVERSION,'2.5','<')) {
                jimport('joomla.html.parameter');
                $this->params = new JParameter($plugin->params);
            } else {
                $this->params = new JRegistry($plugin->params);
            }
        }
        //вытаскиваем в переменные класса объект с конфигом плагина
        $this->pluginConfig = json_decode($config['params']);
    }



    /**
     * Функция инициируется по триггеру в админке HikaShop при просмотре заказа
     * @param $view
     * @return bool
     * @since
     */
    public function onHikashopBeforeDisplayView(&$view) {
        $viewName = $view->getName();
        $layoutName = $view->getLayout();
        //Триггер срабатывает перед загрузкой страницы с информацией о заказе
        if ($viewName === 'order' && $layoutName === 'show') {
            //этот JavaScript добавит в таблицу "Список товаров" на странице информации о заказе в админке HikaShop новый столбец "Вес и габариты"
            $doc = JFactory::getDocument();
            $jscript = "jQuery(document).ready(function() { ";
            //добавляем заголовок нового столбца
            $jscript .= "var thHTML = '<th>Вес и габариты</th>'; ";
            $jscript .= 'jQuery("th.hikashop_order_item_price_title").after(thHTML); ';
            foreach ($view->order->products as $key=>$item) {
                //достаём вес и габариты
                $weight = $this->formatDimension($view->products[$item->product_id]->product_weight);
                $weightUnit = $view->products[$item->product_id]->product_weight_unit;
                $width = $this->formatDimension($view->products[$item->product_id]->product_width);
                $widthUnit = $view->products[$item->product_id]->product_dimension_unit;
                $length = $this->formatDimension($view->products[$item->product_id]->product_length);
                $height = $this->formatDimension($view->products[$item->product_id]->product_height);
                if (empty($this->pluginConfig->display_type)) {
                    $jscript .= "var tdHTML$key = '<td>$weight $weightUnit, $width" . "x" . $length . "x" . "$height $widthUnit </td>'; ";
                } else {
                    $jscript .= <<<HTML
                            var tdHTML$key = '' +
                                '<td>' +
                                '<small>Вес: </small>$weight <small>$weightUnit</small><br />' +
                                '<small>Ширина: </small>$width <small>$widthUnit</small><br />' +
                                '<small>Длина: </small>$length <small>$widthUnit</small><br />' +
                                '<small>Высота: </small>$height <small>$widthUnit</small>' +
                                '</td>';
HTML;
                }
                $jscript .= "jQuery('#hikashop_order_product_listing tbody>tr:eq($key) td.hikashop_order_item_price_value').after(tdHTML$key); ";
            }
            $jscript .= '});';
            //выводим скрипт
            $doc->addScriptDeclaration($jscript);
        }
    }

    /**
     * Вспомогательная функция для отбрасывания лишних нулей в дробной части габаритов и веса
     * @param $float
     * @return string
     * @since
     */
    private function formatDimension($float) {
        return rtrim(rtrim($float,'0'),'.');
    }

}
