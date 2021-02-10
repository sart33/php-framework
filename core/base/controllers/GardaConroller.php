<?php




class GardaConroller
{
    public function actionImport()
    {

        $command = Yii::$app->db->createCommand('TRUNCATE TABLE `offers_garda`')->execute();
        $command = Yii::$app->db->createCommand('TRUNCATE TABLE `offers_garda_group`')->execute();


        $reader = new \XMLReader();



        $reader->open('http://sart.network/xml/garda.yml'); // указываем ридеру что будем парсить этот файл
        // циклическое чтение документа
        while($reader->read()) {
            $xml = array();
            // если ридер находит элемент <offer> запускаются события
            if($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'offer') {

                //  simplexml_import_dom Получает объект класса SimpleXMLElement из узла DOM
                $xml = simplexml_load_string($reader->readOuterXml());
                //            if ($reader->localName == 'offer') {


                // считываем аттрибут number
                // Дальше зная примерную структуру документа внутри узла DOM обращаемя к элементам, сохраняя ключи и значения в массив.


                // v2


                if(isset($xml->id)) $xml['id'] = $xml->id;
                if(isset($xml->available))$xml['available'] = $xml->available;
                if(isset($xml->group_id))$xml['group_id'] = $xml->group_id;
                if (isset($xml->url)) $xml['url'] = $xml->url;
                if (isset($xml->price)) $xml['price'] = $xml->price;
                if (isset($xml->currencyId)) $xml['currencyId'] = $xml->currencyId;
                if (isset($xml->categoryId)) $xml['categoryId'] = $xml->categoryId;

                if (isset($xml->picture)) {
                    $xml['main_img'] = $xml->picture[0];
                    $i=1;
                    foreach ($xml->picture as $value) {

                        $xml['gallery_' . $i] =  $value;
                        $i++;
                    }
                }

                if(isset($xml->name))            $xml['model'] = $xml->name;
                if(isset($xml->vendorCode))            $xml['vendorCode'] = $xml->vendorCode;
                if(isset($xml->description))            $xml['description'] = $xml->description;

                if(isset($xml->param)) {

                    $u = 0;
                    foreach ($xml->param as $value) {
                        $name = $xml->param[$u]['name'];
                        $unit = $xml->param[$u]['unit'];
                        $xml[(string)$name] = $xml->param[$u];
                        $u++;
                    }
                }

//                echo '<pre>' . print_r($xml, true) . '</pre>';

                // В результате получаем массив объектов SimpleXMLElement с теми ключами, которые МЫ ему присвоили

                // Дальше массив нужно перебрать чтобы получился масиив заполненный строками а не объектами

                $xml = $xml->attributes();
//                echo '<pre>' . print_r($xml, true) . '</pre>';

                $json = json_encode( $xml );
                $xml_array = json_decode( $json, true );

                $result  =  $xml_array['@attributes'];
//                echo '<pre>' . print_r($result, true) . '</pre>';

                // Дальше создаем 'OffersKarree' - класс Active Record, который сопоставлен с таблицей offers-karree
                // И используя интерфейс Active Record присваиваем атрибутам OffersKarree значения используя ключи массива $result

                $offers_garda = new OffersGarda();
                if(isset($result['id'])) $offers_garda->id = $result['id'];
                if(isset($result['available'])) $offers_garda->available = $result['available'];
                if(isset($result['group_id'])) $offers_garda->group_id = $result['group_id'];
                if(isset($result['url'])) $offers_garda->url = $result['url'];
                if(isset($result['price'])) $offers_garda->price = $result['price'];
                if(isset($result['currencyId'])) $offers_garda->currency_id = $result['currencyId'];
                if(isset($result['categoryId'])) $offers_garda->category_id = $result['categoryId'];
                if(isset($result['main_img'])) $offers_garda->main_img = $result['main_img'];
                if(isset($result['gallery_1']))   $offers_garda->gallery_1 = $result['gallery_1'];
                if(isset($result['gallery_2']))   $offers_garda->gallery_2 = $result['gallery_2'];
                if(isset($result['gallery_3']))   $offers_garda->gallery_3 = $result['gallery_3'];
                if(isset($result['gallery_4']))   $offers_garda->gallery_4 = $result['gallery_4'];
                if(isset($result['gallery_5']))   $offers_garda->gallery_5 = $result['gallery_5'];
                if(isset($result['model'])) $offers_garda->model = $result['model'];
                if(isset($result['vendorCode'])) $offers_garda->vendor_code = $result['vendorCode'];
                if(isset($result['description'])) $offers_garda->description = $result['description'];
                if(isset($result['sales_notes'])) $offers_garda->sales_notes = $result['sales_notes'];
                if(isset($result['Размер:'])) $offers_garda->sizes = $result['Размер:'];
                if(isset($result['Состав:'])) $offers_garda->composition_name = $result['Состав:'];
                if(isset($result['Цвет'])) $offers_garda->color_name = $result['Цвет'];
                if(isset($result['Бренд'])) $offers_garda->brand_name = $result['Бренд'];
                if(isset($result['Длина'])) $offers_garda->product_length_name = $result['Длина'];
                if(isset($result['Предмет'])) $offers_garda->product_type_name = $result['Предмет'];
                if(isset($result['Ткань'])) $offers_garda->cloth_name = $result['Ткань'];
                if(isset($result['Принт'])) $offers_garda->print_name = $result['Принт'];
                if(isset($result['Стиль'])) $offers_garda->style_name = $result['Стиль'];
                if(isset($result['Сезон'])) $offers_garda->season_name = $result['Сезон'];
                if(isset($result['Рукав'])) $offers_garda->sleeve_name = $result['Рукав'];

                $offers_garda->source = 'garda';

//                echo '<pre>' . print_r($result, true) . '</pre>';


                $offers_garda->save();

                unset($result);
            }
            // Дальше повторяем цикл (XMLReader ищет cследующий <offer>


            if($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'category') {


                if(null !== ($reader->getAttribute('id')))  $result['id'] = $reader->getAttribute('id');

                if(null !== ($reader->readString('id')))  $result['name'] = $reader->readString('id');

                $result['source'] = 'garda';

//                echo '<pre>' . print_r($result, true) . '</pre>';
//


                // Дальше создаем 'OffersKarree' - класс Active Record, который сопоставлен с таблицей offers-karree
                // И используя интерфейс Active Record присваиваем атрибутам OffersKarree значения используя ключи массива $result

//                $categoriesGarda = new CategoriesGarda();
//                if(isset($result['id'])) $categoriesGarda->id = $result['id'];
//                if(isset($result['name'])) $categoriesGarda->name = $result['name'];
//                if(isset($result['source'])) $categoriesGarda->source = $result['source'];

//                $categoriesGarda->save();

//                $importCategory = new ImportCategory();
//                if(isset($result['id'])) $importCategory->import_id = $result['id'];
//                if(isset($result['name'])) $importCategory->name = $result['name'];
//                if(isset($result['source'])) $importCategory->source = $result['source'];

//                $importCategory->save();
            }
            // Дальше повторяем цикл (XMLReader ищет cследующий <offer>



        }
        // Закрывает ввод, который в настоящий момент анализирует объект XMLReader.
        $reader->close();
        return $this->render('import');
    }


    public function actionMagic()
    {


        /*** Определение количества иттераций обращения к db ***/
        $command = Yii::$app->db->createCommand('SELECT MAX(`sort_id`) FROM `offers_garda`')->queryOne();
        $maxIndex =$command['MAX(`sort_id`)'];

        /*** Запрс на получение значений коофициентов в формулу расчета стоимости***/
        $recalculateId = 1;
        $commandsRec = Yii::$app->db->createCommand('
SELECT `id`, `manufacturer`, `a`, `b`, `c`
        FROM `recalculate` WHERE `id` =:id')
            ->bindParam(':id', $recalculateId);
        $postsRec = $commandsRec->queryOne();
        $aRec = $postsRec['a'];
        $bRec = $postsRec['b'];
        $cRec = $postsRec['c'];

        /*** Начало цикла поиска соответствия в словарях для конкретного товара ***/
        $i = 1; $y = 0; $u = 0; $o = 0;
        while ($i<=$maxIndex) {

            /*** Поиск соответствия в словаре attr_color  ***/
            $command1 = Yii::$app->db->createCommand('SELECT `offers_garda`.`sort_id`, `offers_garda`.`id`, `offers_garda`.`available`, `offers_garda`.`group_id`, `offers_garda`.`url`, `offers_garda`.`price`, `offers_garda`.`currency_id`, `offers_garda`.`category_name`, `offers_garda`.`category_id`, `offers_garda`.`source`, `offers_garda`.`main_img`, `offers_garda`.`gallery_1`, `offers_garda`.`gallery_2`, `offers_garda`.`gallery_3`, `offers_garda`.`gallery_4`, `offers_garda`.`gallery_5`, `offers_garda`.`model`, `offers_garda`.`vendor_code`, `offers_garda`.`description`, `offers_garda`.`country_of_origin`, `offers_garda`.`product_type_name`, `offers_garda`.`product_type_id`, `offers_garda`.`sales_notes`, `offers_garda`.`composition_name`, `offers_garda`.`composition_id`, `offers_garda`.`sizes`, `offers_garda`.`sizes_id`, `offers_garda`.`silhouette_name`, `offers_garda`.`silhouette_id`, `offers_garda`.`cloth_name`, `offers_garda`.`cloth_id`, `attr_color`.`name_displayed`, `attr_color`.`color_id`, `attr_color`.`sort_name_displayed`, `attr_color`.`sort_color_id`, `offers_garda`.`print_name`, `offers_garda`.`print_id`, `offers_garda`.`style_name`, `offers_garda`.`style_id`, `offers_garda`.`season_name`, `offers_garda`.`season_id`, `offers_garda`.`brand_name`, `offers_garda`.`brand_id`, `offers_garda`.`product_length_name`, `offers_garda`.`product_length_id`, `offers_garda`.`sleeve_name`, `offers_garda`.`sleeve_id`
FROM `offers_garda`, `attr_color` WHERE `offers_garda`.`color_name` = `attr_color`.`input_name` && `offers_garda`.`sort_id` =:sort_id')
                ->bindParam(':sort_id', $i);

            $post1 = $command1->queryOne();

            $values1[$i] = $post1;
            $u = $values1[$i]['sort_id'];
            $name_displayed = $values1[$i]['name_displayed'];
            $color_id = $values1[$i]['color_id'];
            $sort_name_displayed = $values1[$i]['sort_name_displayed'];
            $sort_color_id = $values1[$i]['sort_color_id'];

            if ($u > 0) {
                $command2 = Yii::$app->db->createCommand('UPDATE `offers_garda` SET `color_name` =:color_name, `color_id` =:color_id, `sort_color_name` =:sort_color_name, `sort_color_id` =:sort_color_id  WHERE sort_id=:sort_id'
                )
                    ->bindParam(':color_name', $name_displayed)
                    ->bindParam(':color_id', $color_id)
                    ->bindParam(':sort_color_name', $sort_name_displayed)
                    ->bindParam(':sort_color_id', $sort_color_id)
                    ->bindParam(':sort_id', $u);
                $command2->execute();
            } else {
            }

            $command3 = Yii::$app->db->createCommand('SELECT `offers_garda`.`sort_id`, `offers_garda`.`id`, `offers_garda`.`available`, `offers_garda`.`group_id`, `offers_garda`.`url`, `offers_garda`.`price`, `offers_garda`.`currency_id`, `offers_garda`.`category_name`, `offers_garda`.`category_id`, `offers_garda`.`source`, `offers_garda`.`main_img`, `offers_garda`.`gallery_1`, `offers_garda`.`gallery_2`, `offers_garda`.`gallery_3`, `offers_garda`.`gallery_4`, `offers_garda`.`gallery_5`, `offers_garda`.`model`, `offers_garda`.`vendor_code`, `offers_garda`.`description`, `offers_garda`.`country_of_origin`, `offers_garda`.`product_type_name`, `offers_garda`.`product_type_id`, `offers_garda`.`sales_notes`, `attr_composition`.`name_displayed`, `attr_composition`.`composition_id`, `offers_garda`.`sizes`, `offers_garda`.`sizes_id`, `offers_garda`.`silhouette_name`, `offers_garda`.`silhouette_id`, `offers_garda`.`cloth_name`, `offers_garda`.`cloth_id`, `offers_garda`.`color_name`, `offers_garda`.`color_id`, `offers_garda`.`print_name`, `offers_garda`.`print_id`, `offers_garda`.`style_name`, `offers_garda`.`style_id`, `offers_garda`.`season_name`, `offers_garda`.`season_id`, `offers_garda`.`brand_name`, `offers_garda`.`brand_id`, `offers_garda`.`product_length_name`, `offers_garda`.`product_length_id`, `offers_garda`.`sleeve_name`, `offers_garda`.`sleeve_id`
FROM `offers_garda`, `attr_composition` WHERE `offers_garda`.`composition_name` = `attr_composition`.`input_name` && `offers_garda`.`sort_id` =:sort_id')
                ->bindParam(':sort_id', $i);

            $post2 = $command3->queryOne();

            $values2[$i] = $post2;
            $y = $values2[$i]['sort_id'];
            $name_displayed = $values2[$i]['name_displayed'];
            $composition_id = $values2[$i]['composition_id'];

            if ($y > 0) {
                $command4 = Yii::$app->db->createCommand('UPDATE `offers_garda` SET `composition_name` =:composition_name, `composition_id` =:composition_id  WHERE sort_id=:sort_id'
                )
                    ->bindParam(':composition_name', $name_displayed)
                    ->bindParam(':composition_id', $composition_id)
                    ->bindParam(':sort_id', $y);
                $command4->execute();
            } else {
            }

            $command5 = Yii::$app->db->createCommand('SELECT `offers_garda`.`sort_id`, `offers_garda`.`id`, `offers_garda`.`available`, `offers_garda`.`group_id`, `offers_garda`.`url`, `offers_garda`.`price`, `offers_garda`.`currency_id`, `offers_garda`.`category_name`, `offers_garda`.`category_id`, `offers_garda`.`source`, `offers_garda`.`main_img`, `offers_garda`.`gallery_1`, `offers_garda`.`gallery_2`, `offers_garda`.`gallery_3`, `offers_garda`.`gallery_4`, `offers_garda`.`gallery_5`, `offers_garda`.`model`, `offers_garda`.`vendor_code`, `offers_garda`.`description`, `offers_garda`.`country_of_origin`, `attr_types`.`name_displayed`, `attr_types`.`types_id`, `offers_garda`.`sales_notes`, `offers_garda`.`composition_name`, `offers_garda`.`composition_id`, `offers_garda`.`sizes`, `offers_garda`.`sizes_id`, `offers_garda`.`silhouette_name`, `offers_garda`.`silhouette_id`, `offers_garda`.`cloth_name`, `offers_garda`.`cloth_id`, `offers_garda`.`color_name`, `offers_garda`.`color_id`, `offers_garda`.`print_name`, `offers_garda`.`print_id`, `offers_garda`.`style_name`, `offers_garda`.`style_id`, `offers_garda`.`season_name`, `offers_garda`.`season_id`, `offers_garda`.`brand_name`, `offers_garda`.`brand_id`, `offers_garda`.`product_length_name`, `offers_garda`.`product_length_id`, `offers_garda`.`sleeve_name`, `offers_garda`.`sleeve_id`
FROM `offers_garda`, `attr_types` WHERE `offers_garda`.`product_type_name` = `attr_types`.`input_name` && `offers_garda`.`sort_id` =:sort_id')
                ->bindParam(':sort_id', $i);

            $post3 = $command5->queryOne();

            $values3[$i] = $post3;
            $o = $values3[$i]['sort_id'];
            $name_displayed = $values3[$i]['name_displayed'];
            $types_id = $values3[$i]['types_id'];

            if ($o > 0) {
                $command6 = Yii::$app->db->createCommand('UPDATE `offers_garda` SET `product_type_name` =:product_type_name, `product_type_id` =:product_type_id  WHERE sort_id=:sort_id'
                )
                    ->bindParam(':product_type_name', $name_displayed)
                    ->bindParam(':product_type_id', $types_id)
                    ->bindParam(':sort_id', $o);
                $command6->execute();
            } else {
            }

            $command7 = Yii::$app->db->createCommand('
SELECT `offers_garda`.`sort_id`, `offers_garda`.`id`, `offers_garda`.`available`, `offers_garda`.`group_id`, `offers_garda`.`url`, `offers_garda`.`price`, `offers_garda`.`currency_id`, `offers_garda`.`category_name`, `offers_garda`.`category_id`, `offers_garda`.`source`, `offers_garda`.`main_img`, `offers_garda`.`gallery_1`, `offers_garda`.`gallery_2`, `offers_garda`.`gallery_3`, `offers_garda`.`gallery_4`, `offers_garda`.`gallery_5`, `offers_garda`.`model`, `offers_garda`.`vendor_code`, `offers_garda`.`description`, `offers_garda`.`country_of_origin`, `offers_garda`.`product_type_name`, `offers_garda`.`product_type_id`, `offers_garda`.`sales_notes`, `offers_garda`.`composition_name`, `offers_garda`.`composition_id`, `attr_sizes`.`name_displayed`, `attr_sizes`.`sizes_id`, `offers_garda`.`silhouette_name`, `offers_garda`.`silhouette_id`, `offers_garda`.`cloth_name`, `offers_garda`.`cloth_id`, `offers_garda`.`color_name`, `offers_garda`.`color_id`, `offers_garda`.`print_name`, `offers_garda`.`print_id`, `offers_garda`.`style_name`, `offers_garda`.`style_id`, `offers_garda`.`season_name`, `offers_garda`.`season_id`, `offers_garda`.`brand_name`, `offers_garda`.`brand_id`, `offers_garda`.`product_length_name`, `offers_garda`.`product_length_id`, `offers_garda`.`sleeve_name`, `offers_garda`.`sleeve_id`
FROM `offers_garda`, `attr_sizes` WHERE `offers_garda`.`sizes` = `attr_sizes`.`input_name` && `offers_garda`.`sort_id` =:sort_id')
                ->bindParam(':sort_id', $i);

            $post4 = $command7->queryOne();

            $values4[$i] = $post4;
            $z = $values4[$i]['sort_id'];
            $name_displayed = $values4[$i]['name_displayed'];
            $sizes_id = $values4[$i]['sizes_id'];

            if ($z > 0) {
                $command8 = Yii::$app->db->createCommand('UPDATE `offers_garda` SET `sizes` =:sizes, `sizes_id` =:sizes_id  WHERE sort_id=:sort_id'
                )
                    ->bindParam(':sizes', $name_displayed)
                    ->bindParam(':sizes_id', $sizes_id)
                    ->bindParam(':sort_id', $z);
                $command8->execute();
            } else {
            }

            $command9 = Yii::$app->db->createCommand('
SELECT `offers_garda`.`sort_id`, `offers_garda`.`id`, `offers_garda`.`available`, `offers_garda`.`group_id`, `offers_garda`.`url`, `offers_garda`.`price`, `offers_garda`.`currency_id`, `offers_garda`.`category_name`, `offers_garda`.`category_id`, `offers_garda`.`source`, `offers_garda`.`main_img`, `offers_garda`.`gallery_1`, `offers_garda`.`gallery_2`, `offers_garda`.`gallery_3`, `offers_garda`.`gallery_4`, `offers_garda`.`gallery_5`, `offers_garda`.`model`, `offers_garda`.`vendor_code`, `offers_garda`.`description`, `offers_garda`.`country_of_origin`, `offers_garda`.`product_type_name`, `offers_garda`.`product_type_id`, `offers_garda`.`sales_notes`, `offers_garda`.`composition_name`, `offers_garda`.`composition_id`, `offers_garda`.`sizes`, `offers_garda`.`sizes_id`, `offers_garda`.`silhouette_name`, `offers_garda`.`silhouette_id`, `offers_garda`.`cloth_name`, `offers_garda`.`cloth_id`, `offers_garda`.`color_name`, `offers_garda`.`color_id`, `offers_garda`.`print_name`, `offers_garda`.`print_id`, `offers_garda`.`style_name`, `offers_garda`.`style_id`, `offers_garda`.`season_name`, `offers_garda`.`season_id`, `offers_garda`.`brand_name`, `offers_garda`.`brand_id`, `attr_product_length`.`name_displayed`, `attr_product_length`.`product_length_id`, `offers_garda`.`sleeve_name`, `offers_garda`.`sleeve_id`
FROM `offers_garda`, `attr_product_length` WHERE `offers_garda`.`product_length_name` = `attr_product_length`.`input_name` && `offers_garda`.`sort_id` =:sort_id')
                ->bindParam(':sort_id', $i);

            $post5 = $command9->queryOne();

            $values5[$i] = $post5;
            $pl = $values5[$i]['sort_id'];
            $name_displayed = $values5[$i]['name_displayed'];
            $product_length_id = $values5[$i]['product_length_id'];
            if ($pl > 0) {
                $command10 = Yii::$app->db->createCommand('UPDATE `offers_garda` SET `product_length_name` =:product_length_name, `product_length_id` =:product_length_id  WHERE sort_id=:sort_id'
                )
                    ->bindParam(':product_length_name', $name_displayed)
                    ->bindParam(':product_length_id', $product_length_id)
                    ->bindParam(':sort_id', $pl);
                $command10->execute();
            } else {
            }

            $command11 = Yii::$app->db->createCommand('
SELECT `offers_garda`.`sort_id`, `offers_garda`.`id`, `offers_garda`.`available`, `offers_garda`.`group_id`, `offers_garda`.`url`, `offers_garda`.`price`, `offers_garda`.`currency_id`, `offers_garda`.`category_name`, `offers_garda`.`category_id`, `offers_garda`.`source`, `offers_garda`.`main_img`, `offers_garda`.`gallery_1`, `offers_garda`.`gallery_2`, `offers_garda`.`gallery_3`, `offers_garda`.`gallery_4`, `offers_garda`.`gallery_5`, `offers_garda`.`model`, `offers_garda`.`vendor_code`, `offers_garda`.`description`, `offers_garda`.`country_of_origin`, `offers_garda`.`product_type_name`, `offers_garda`.`product_type_id`, `offers_garda`.`sales_notes`, `offers_garda`.`composition_name`, `offers_garda`.`composition_id`, `offers_garda`.`sizes`, `offers_garda`.`sizes_id`, `offers_garda`.`silhouette_name`, `offers_garda`.`silhouette_id`, `offers_garda`.`cloth_name`, `offers_garda`.`cloth_id`, `offers_garda`.`color_name`, `offers_garda`.`color_id`, `offers_garda`.`print_name`, `offers_garda`.`print_id`, `offers_garda`.`style_name`, `offers_garda`.`style_id`, `offers_garda`.`season_name`, `offers_garda`.`season_id`, `attr_brand`.`name_displayed`, `attr_brand`.`brand_id`, `offers_garda`.`product_length_name`, `offers_garda`.`product_length_id`, `offers_garda`.`sleeve_name`, `offers_garda`.`sleeve_id`
FROM `offers_garda`, `attr_brand` WHERE `offers_garda`.`brand_name` = `attr_brand`.`input_name` && `offers_garda`.`sort_id` =:sort_id')
                ->bindParam(':sort_id', $i);

            $post6 = $command11->queryOne();

            $values6[$i] = $post6;
            $pr = $values6[$i]['sort_id'];
            $name_displayed = $values6[$i]['name_displayed'];
            $brand_id = $values6[$i]['brand_id'];
            if ($pr > 0) {
                $command12 = Yii::$app->db->createCommand('UPDATE `offers_garda` SET `brand_name` =:brand_name, `brand_id` =:brand_id  WHERE sort_id=:sort_id'
                )
                    ->bindParam(':brand_name', $name_displayed)
                    ->bindParam(':brand_id', $brand_id)
                    ->bindParam(':sort_id', $pr);
                $command12->execute();
            } else {
            }

            $command13 = Yii::$app->db->createCommand('
SELECT `offers_garda`.`sort_id`, `offers_garda`.`id`, `match_table_garda`.`param_id`, `offers_garda`.`available`, `offers_garda`.`group_id`, `offers_garda`.`url`, `offers_garda`.`price`, `offers_garda`.`currency_id`, `match_table_garda`.`name_cat`, `match_table_garda`.`catalog_id`, `offers_garda`.`source`, `offers_garda`.`main_img`, `offers_garda`.`gallery_1`, `offers_garda`.`gallery_2`, `offers_garda`.`gallery_3`, `offers_garda`.`gallery_4`, `offers_garda`.`gallery_5`, `offers_garda`.`model`, `offers_garda`.`vendor_code`, `offers_garda`.`description`, `offers_garda`.`country_of_origin`, `offers_garda`.`product_type_name`, `offers_garda`.`product_type_id`, `offers_garda`.`sales_notes`, `offers_garda`.`composition_name`, `offers_garda`.`composition_id`, `offers_garda`.`sizes`, `offers_garda`.`sizes_id`, `offers_garda`.`silhouette_name`, `offers_garda`.`silhouette_id`, `offers_garda`.`cloth_name`, `offers_garda`.`cloth_id`, `offers_garda`.`color_name`, `offers_garda`.`color_id`, `offers_garda`.`print_name`, `offers_garda`.`print_id`, `offers_garda`.`style_name`, `offers_garda`.`style_id`, `offers_garda`.`season_name`, `offers_garda`.`season_id`, `offers_garda`.`product_length_name`, `offers_garda`.`product_length_id`, `offers_garda`.`sleeve_name`, `offers_garda`.`sleeve_id`
FROM `offers_garda`, `match_table_garda` WHERE `offers_garda`.`category_id` = `match_table_garda`.`garda_id` && `offers_garda`.`sort_id` =:sort_id')
                ->bindParam(':sort_id', $i);

            $post8 = $command13->queryOne();

            $values7[$i] = $post8;
            $pr = $values7[$i]['sort_id'];
            $name_displayed = $values7[$i]['name_cat'];
            $catalog_id = $values7[$i]['catalog_id'];
            $param_id = $values7[$i]['param_id'];
            if ($pr > 0) {
                $command14 = Yii::$app->db->createCommand('UPDATE `offers_garda` SET `category_name` =:category_name, `category_id` =:category_id, `param_id` =:param_id  WHERE sort_id=:sort_id'
                )
                    ->bindParam(':category_name', $name_displayed)
                    ->bindParam(':category_id', $catalog_id)
                    ->bindParam(':param_id', $param_id)
                    ->bindParam(':sort_id', $pr);
                $command14->execute();
            } else {
            }

            $command15 = Yii::$app->db->createCommand('SELECT `sort_id`, `price`
FROM `offers_garda` WHERE `sort_id` =:sort_id')
                ->bindParam(':sort_id', $i);

            $post9 = $command15->queryOne();

            $values9[$i] = $post9;
            $pr9 = $values9[$i]['sort_id'];
            $price = $values9[$i]['price'];
            $priceResult = round((($price + $aRec) * $bRec) + $cRec);


            if ($pr9 > 0) {
                $command16 = Yii::$app->db->createCommand('UPDATE `offers_garda` SET `price` =:price  WHERE sort_id=:sort_id'
                )
                    ->bindParam(':price', $priceResult)
                    ->bindParam(':sort_id', $pr9);
                $command16->execute();
            } else {
            }

            $i++;
        }
        return $this->render('magic');
    }

    public function actionSphinx()
    {
        return $this->render('sphinx');
    }

    public function actionGroup()
    {
        $i = 1;
        $y = 1;
        $qtr = -1;
        $count = 0;
        $values = [];

        $command = Yii::$app->db->createCommand('TRUNCATE TABLE `offers_garda_group`')->execute();


        $command = Yii::$app->db->createCommand('SELECT MAX(`sort_id`) FROM `offers_garda`')->queryOne();
        $maxIndex = $command['MAX(`sort_id`)'];


        while ($i <= $maxIndex) {
            $command = Yii::$app->db->createCommand('SELECT `sort_id`, `id`, `param_id`, `available`, `group_id`, `url`, `price`, `currency_id`, `category_name`, `category_id`, `source`, `main_img`, `gallery_1`, `gallery_2`, `gallery_3`, `gallery_4`, `gallery_5`, `model`, `vendor_code`, `description`, `country_of_origin`, `product_type_name`, `product_type_id`, `sales_notes`, `composition_name`, `composition_id`, `sizes`, `sizes_id`, `silhouette_name`, `silhouette_id`, `cloth_name`, `cloth_id`, `color_name`, `color_id`, `sort_color_name`, `sort_color_id`, `print_name`, `print_id`, `style_name`, `style_id`, `season_name`, `season_id`, `brand_name`, `brand_id`, `product_length_name`, `product_length_id`, `sleeve_name`, `sleeve_id` 
FROM `offers_garda` WHERE `sort_id`=:sort_id')
                ->bindParam(':sort_id', $i);

            $post = $command->queryOne();
            $values[$i] = $post;

            $values[0] = 0;
//            $values[0]['group_id'] = 0;


            if ($values[$i]['group_id'] === $values[$i - 1]['group_id']) {
                $count++;
                $values[$i]['count'] = $count;

            }
            if ($values[$i]['group_id'] !== $values[$i - 1]['group_id']) {
                $count = 0;
                $y++;
                $values[$i]['count'] = $count;
                $values[$i]['y'] = $y;
            }

//            echo $count . '<br>';
//            '<pre>' . print_r($values) . '</pre>';

//            echo 'count: ' . $count . ',  y: ' . $y . ', i: ' . $i . '<br>';

            $i++;
        }

        for ($i = 1; $i <= $maxIndex; $i++) {

            if ($values[$i]['count'] == 0) {
                $offers_garda_group = new OffersGardaGroup();
//                if (isset($values[$i]['sort_id'])) $offers_garda_group->sort_id = $values[$i]['id'];
//                if (isset($values[$i]['id'])) $offers_garda_group->id = $values[$i]['id'];

                $sku  = 'gd' . substr($values[$i]['group_id'], 0, 5);

                if(isset($values[$i]['group_id']))$offers_garda_group->sku = $sku;
                if(isset($values[$i]['param_id']))$offers_garda_group->param_id = $values[$i]['param_id'];
                if(isset($values[$i]['available']))$offers_garda_group->available = $values[$i]['available'];
                if(isset($values[$i]['url']))$offers_garda_group->url = $values[$i]['url'];
                if(isset($values[$i]['price']))$offers_garda_group->price = $values[$i]['price'];
                if(isset($values[$i]['currency_id']))$offers_garda_group->currency_id = $values[$i]['currency_id'];
                if(isset($values[$i]['category_name'])) $offers_garda_group->category_name = $values[$i]['category_name'];
                if(isset($values[$i]['category_id'])) $offers_garda_group->category_id = $values[$i]['category_id'];
                if(isset($values[$i]['main_img']))$offers_garda_group->main_img = $values[$i]['main_img'];
                if(isset($values[$i]['gallery_1']))  $offers_garda_group->gallery_1 = $values[$i]['gallery_1'];
                if(isset($values[$i]['gallery_2']))  $offers_garda_group->gallery_2 = $values[$i]['gallery_2'];
                if(isset($values[$i]['gallery_3']))  $offers_garda_group->gallery_3 = $values[$i]['gallery_3'];
                if(isset($values[$i]['gallery_4']))  $offers_garda_group->gallery_4 = $values[$i]['gallery_4'];
                if(isset($values[$i]['gallery_5']))  $offers_garda_group->gallery_5 = $values[$i]['gallery_5'];
                if(isset($values[$i]['model'])) $offers_garda_group->model = $values[$i]['model'];
                if(isset($values[$i]['vendor_code']))$offers_garda_group->vendor_code = $values[$i]['vendor_code'];
                if(isset($values[$i]['description']))$offers_garda_group->description = $values[$i]['description'];
                if(isset($values[$i]['country_of_origin']))$offers_garda_group->country_of_origin = $values[$i]['country_of_origin'];
                if(isset($values[$i]['product_type_name']))$offers_garda_group->product_type_name = $values[$i]['product_type_name'];
                if(isset($values[$i]['product_type_id']))$offers_garda_group->product_type_id = $values[$i]['product_type_id'];
                if(isset($values[$i]['sales_notes']))$offers_garda_group->sales_notes = $values[$i]['sales_notes'];
                if(isset($values[$i]['composition_name']))$offers_garda_group->composition_name = $values[$i]['composition_name'];
                if(isset($values[$i]['composition_id']))$offers_garda_group->composition_id = $values[$i]['composition_id'];
                if(isset($values[$i]['silhouette_name']))$offers_garda_group->silhouette_name = $values[$i]['silhouette_name'];
                if(isset($values[$i]['silhouette_id']))$offers_garda_group->silhouette_id = $values[$i]['silhouette_id'];
                if(isset($values[$i]['cloth_name']))$offers_garda_group->cloth_name = $values[$i]['cloth_name'];
                if(isset($values[$i]['cloth_id']))$offers_garda_group->cloth_id = $values[$i]['cloth_id'];
                if(isset($values[$i]['color_name']))$offers_garda_group->color_name = $values[$i]['color_name'];
                if(isset($values[$i]['color_id']))$offers_garda_group->color_id = $values[$i]['color_id'];
                if(isset($values[$i]['sort_color_name']))$offers_garda_group->sort_color_name = $values[$i]['sort_color_name'];
                if(isset($values[$i]['sort_color_id']))$offers_garda_group->sort_color_id = $values[$i]['sort_color_id'];
                if(isset($values[$i]['print_name']))$offers_garda_group->print_name = $values[$i]['print_name'];
                if(isset($values[$i]['print_id']))$offers_garda_group->print_id = $values[$i]['print_id'];
                if(isset($values[$i]['style_name']))$offers_garda_group->style_name = $values[$i]['style_name'];
                if(isset($values[$i]['style']))$offers_garda_group->style_id = $values[$i]['style'];
                if(isset($values[$i]['brand_name']))$offers_garda_group->brand_name = $values[$i]['brand_name'];
                if(isset($values[$i]['brand_id']))$offers_garda_group->brand_id = $values[$i]['brand_id'];
                if(isset($values[$i]['season_name']))$offers_garda_group->season_name = $values[$i]['season_name'];
                if(isset($values[$i]['season_id']))$offers_garda_group->season_id = $values[$i]['season_id'];
                if(isset($values[$i]['product_length_name']))$offers_garda_group->product_length_name = $values[$i]['product_length_name'];
                if(isset($values[$i]['product_length_id']))$offers_garda_group->product_length_id = $values[$i]['product_length_id'];
                if(isset($values[$i]['sleeve_name']))$offers_garda_group->sleeve_name = $values[$i]['sleeve_name'];
                if(isset($values[$i]['sleeve_id']))$offers_garda_group->sleeve_id = $values[$i]['sleeve_id'];
                if (isset($values[$i]['sizes']) && $values[$i]['count'] === 0) $offers_garda_group->sizes_1 = $values[$i]['sizes'];
                if (isset($values[$i]['sizes_id']) && $values[$i]['count'] === 0) $offers_garda_group->sizes_1_id = $values[$i]['sizes_id'];
                if (isset($values[$i+1]['sizes']) && $values[$i + 1]['count'] === 1) $offers_garda_group->sizes_2 = $values[$i+1]['sizes'];
                if (isset($values[$i+1]['sizes_id']) && $values[$i + 1]['count'] === 1) $offers_garda_group->sizes_2_id = $values[$i+1]['sizes_id'];
                if (isset($values[$i+2]['sizes']) && $values[$i + 2]['count'] === 2) $offers_garda_group->sizes_3 = $values[$i+2]['sizes'];
                if (isset($values[$i+2]['sizes_id']) && $values[$i + 2]['count'] === 2) $offers_garda_group->sizes_3_id = $values[$i+2]['sizes_id'];
                if (isset($values[$i+3]['sizes']) && $values[$i + 3]['count'] === 3) $offers_garda_group->sizes_4 = $values[$i+3]['sizes'];
                if (isset($values[$i+3]['sizes_id']) && $values[$i + 3]['count'] === 3) $offers_garda_group->sizes_4_id = $values[$i+3]['sizes_id'];
                if (isset($values[$i+4]['sizes']) && $values[$i + 4]['count'] === 4) $offers_garda_group->sizes_5 = $values[$i+4]['sizes'];
                if (isset($values[$i+4]['sizes_id']) && $values[$i + 4]['count'] === 4) $offers_garda_group->sizes_5_id = $values[$i+4]['sizes_id'];
                if (isset($values[$i+5]['sizes']) && $values[$i + 5]['count'] === 5) $offers_garda_group->sizes_6 = $values[$i+5]['sizes'];
                if (isset($values[$i+5]['sizes_id']) && $values[$i + 5]['count'] === 5) $offers_garda_group->sizes_6_id = $values[$i+5]['sizes_id'];

//                if (isset($values[$i+1]['sizes'])) echo $values[$i+1]['sizes'] . '-';
//                if (isset($values[$i+2]['sizes'])) echo $values[$i+2]['sizes'];
//                if (isset($values[$i+3]['sizes'])) echo $values[$i+3]['sizes'];
//                if (isset($values[$i+4]['sizes'])) echo $values[$i+4]['sizes'];
//                if (isset($values[$i+5]['sizes'])) echo '-' . $values[$i+5]['sizes'];
//                echo '<br>';



                $offers_garda_group->source = 'garda';

                $offers_garda_group->save();
            } else {
//                echo 'Повтор';
            }
//          $i++;
        }



        // Дальше повторяем цикл (XMLReader ищет cследующий <offer>





        // Закрывает ввод, который в настоящий момент анализирует объект XMLReader.

        return $this->render('group');
    }

}