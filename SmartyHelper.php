<?php
namespace Helpers;

class SmartyHelper {


    public static function smartyBoxes($pages, $options = array())
    {
        $data = array();

        if (!empty($pages))
        {
            foreach ( $pages as $page )
            {
                $item            = array();
                $item['image']   = null;
                if (!empty($options['withProduct']))
                {
                    $item['product'] = null;

                    $bannerProduct  = $page->getProductChooserData();

                    if (!empty($bannerProduct))
                    {
                        $bannerProduct  = current($bannerProduct);
                        $product = \Product::findById($bannerProduct);
                        $item['product']['id']  = $product->id;
                        $item['product']['url'] = $product->url;
                    }
                }

                $item['url']    = null;
                $item['target'] = null;
                $imagePath      = APP_PATH . $page->ff_1_file;

                // Obrazek
                if ( $page->ff_1_file && is_readable($imagePath) )
                {
                    $imagesSizes             = getimagesize($imagePath);
                    $item['image']['width']  = $imagesSizes[0];
                    $item['image']['height'] = $imagesSizes[1];
                    $item['image']['url']    = $page->ff_1_file;
                }

                // URL
                if ( in_array($page->pageTemplate, array('pt_link', 'pt_banner')) )
                {
                    $item['url']    = $page->tf_1;
                    $item['target'] = $page->getTarget();
                }

                $item['title']   = $page->ff_1_title;
                $item['title']   = $page->fullTitle;
                $item['text']    = $page->ha_1;

                if ($page->pageTemplate == 'pt_banner')
                {
                    $item['className'] = $page->tf_2;
                }

                $data['boxes'][] = $item;
            }
        }
        return $data;

    }
    

    public static function smartyMenuBoxes($pages)
    {
        $data = array();
        if (!empty($pages))
        {
            foreach ($pages as $page)
            {
                if ( !$page || !$page->enabled || !$page->authorized( $_SESSION['client']->userType ) )
                    continue;

                $item            = array();
                $item['title']   = $page->menuTitle;
                $item['items'] = self::smartyMenu($page, 1);

                if ( ( !$item['title'] && $page->pageTemplate != 'pt_empty' ) || !$item['items'] )
                    continue;

                $data[] = $item;
            }
        }
        return $data;
    }
    
    public static function smartyMenu(\Page $parentPage, $level, $dataObject = null)
    {
        $data = array();

        $data['rootPage'] = array(
            'href'    => $parentPage->url,
            'title'   => $parentPage->fullTitle,
            'target'  => '_self',
            'heading' => $parentPage->fullTitle,
            'text'    => $parentPage->menuTitle,
        );

        $data['menuBar'] = self::smartyMenuRecursive($parentPage, $level);

        $data['selected']['url']  = \Env::getInstance()->getUri(0);
        $data['selected']['path']  = self::parseUrlToArray($data['selected']['url']);
        
        if (!empty($dataObject))
        {
            if (isset($dataObject) && $dataObject instanceOf \Product) {
                if (!$dataObject->isTecdoc()) {
                    $product_categories = $dataObject->getCategoriesIds();
                    $manufacturerId = $dataObject->getManufacturerId();

                    $findLast = true;

                    if (isset($_SESSION['lastCategory'])) {
                        if ($_SESSION['lastCategory'] instanceOf \Category && in_array($_SESSION['lastCategory']->id, $product_categories)) {
                            $data['selected']['url'] = '/'.$_SESSION['lastCategory']->getUri();
                            $data['selected']['path'] = self::parseUrlToArray($data['selected']['url']);
                        } else if ($_SESSION['lastCategory'] instanceOf \Manufacturer && $_SESSION['lastCategory']->id == $manufacturerId) {
                            $data['selected']['url'] = '/'.$_SESSION['lastCategory']->seo;
                            $data['selected']['path'] = self::parseUrlToArray($data['selected']['url']);
                        } else {
                            $findLast = false;
                        }
                    } else {
                        $findLast = false;
                    }

                    if (!$findLast) {
                        $data['selected']['url'] = \Page::getUrl($dataObject->getPrimaryCategory()->id, 0);
                        $data['selected']['path'] = self::parseUrlToArray($data['selected']['url']);
                    }
                }

            }
        }

        return $data;
    }

    public static function smartyMenuTecdoc(\Page $parentPage, $level, $dataObject = null, $showEmptySelects = false)
    {
        $data = array();

        $data['rootPage'] = array(
            'href' => $parentPage->url,
            'title' => $parentPage->fullTitle,
            'target' => '_self',
            'heading' => $parentPage->fullTitle,
            'text' => $parentPage->menuTitle,
        );


        $selectedUrl = \Env::getInstance()->getUri(0);
        $selectedPath = self::parseUrlToArray($selectedUrl);

        $data['selected']['url']  = $selectedUrl;
        $data['selected']['path']  = $selectedPath;


        // rodicovska kategoria znaciek
        $data['menuBar'] = self::smartyMenuRecursive($parentPage, $level);

        if (!empty($data['menuBar'])) {
            if (!\Configurator::get('tecdoc_cv') || !\Configurator::get('tecdoc_pc'))  {
                foreach ($data['menuBar'] as $key => $menu) {
                    if (!\Configurator::get('tecdoc_cv')) {
                        if ($menu['id'] == \Model_Category::RS_TECDOC_BRANDS_CV_PARENT_ID) {
                            unset($data['menuBar'][$key]);
                        }
                    }
                    if (!\Configurator::get('tecdoc_pc')) {
                        if ($menu['id'] == \Model_Category::RS_TECDOC_BRANDS_PARENT_ID) {
                            unset($data['menuBar'][$key]);
                        }
                    }
                }
            }

            $ident = 0;

            if (!empty($data['menuBar'][1]) && (
                in_array($data['menuBar'][1]['href'], $selectedPath) || empty($data['menuBar'][0])
            )) {
                $ident = 1;
            } elseif (!empty($data['menuBar'][0]) && (
                in_array($data['menuBar'][0]['href'], $selectedPath) || empty($data['menuBar'][1])
            )) {
                $ident = 0;
            } elseif (isset($_SESSION['tecdocMenuIdent'])) {
                $ident = $_SESSION['tecdocMenuIdent'];
            }
            $_SESSION['tecdocMenuIdent'] = $ident;


            if (\Configurator::get('tecdoc_pc')) {
                $vehicleType = \Model_Category::TYPE_TECDOC_MAN_TO_CATEGORY_PC;
            } else if (\Configurator::get('tecdoc_cv')) {
                $vehicleType = \Model_Category::TYPE_TECDOC_MAN_TO_CATEGORY_CV;
            }

            // znacky nacitame len ak sme v rodicovskej kategorii
            if (!empty($data['menuBar'][$ident])) {
                if (!in_array($data['menuBar'][$ident]['href'], $selectedPath)) {
                    $ok = false;
                    if (!empty($dataObject)) {
                        if (isset($dataObject) && $dataObject instanceOf \Product) {
                            // nacitame data pre produkt
                            if (isset($_SESSION['lastTecdocPath']) && !empty($_SESSION['lastTecdocPath'])) {
                                $ok = $dataObject->checkAgainstTecdocAllocations($_SESSION['lastTecdocPath']);
                                if ($ok) {
                                    $selectedUrl = $_SESSION['lastTecdocPath']['url'];
                                    $selectedPath = self::parseUrlToArray($selectedUrl);
                                    // check again for product
                                    if (!empty($data['menuBar'][1]) && in_array($data['menuBar'][1]['href'], $selectedPath)) {
                                        $ident = 1;
                                    }
                                }
                            }
                        }
                    }

                    if (!$ok) {
                        if (!$showEmptySelects) {
                            return $data;
                        }
                    }
                }

                if ($data['menuBar'][$ident]['id'] == \Model_Category::RS_TECDOC_BRANDS_CV_PARENT_ID) {
                    $vehicleType = \Model_Category::TYPE_TECDOC_MAN_TO_CATEGORY_CV;
                }

                $data['selected']['vehicleType'] = $vehicleType;

                if ($showEmptySelects) {
                    $data['menuBar'][$ident]['tecdocModels'] = array(0);
                    $data['menuBar'][$ident]['tecdocMotorizations'] = array(0);
                }

                // znacky
                $data['menuBar'][$ident]['tecdocBrands'] = $data['menuBar'][$ident]['subMenu'];
                unset($data['menuBar'][$ident]['subMenu']);


                $selectedBrand = self::getSelectedByUrl($data['menuBar'][$ident]['tecdocBrands'], $selectedPath);
            }

            if (!empty($selectedBrand)) {
                // modely
                $models = \TecdocModelPage::getModelsByBrand(null, $selectedBrand['id']);

                $data['menuBar'][$ident]['tecdocModelsObjects'] = $models;
                $data['menuBar'][$ident]['tecdocModels'] = self::smartyMenuTecdocItems($models);
                $data['menuBar'][$ident]['selectedBrandHref'] = $selectedBrand['href'];

                $selectedModel = self::getSelectedByUrl($models, $selectedPath);

                if (!empty($selectedModel)) {
                    // znacky
                    $motorizations = \TecdocMotorizationPage::getMotorizationsByModel($selectedModel->id, $vehicleType);

                    $data['menuBar'][$ident]['tecdocMotorizations'] = self::smartyMenuTecdocItems($motorizations);
                    $data['menuBar'][$ident]['selectedModelHref'] = $selectedModel->url;

                    $selectedMotorization = self::getSelectedByUrl($motorizations, $selectedPath);

                    if (!empty($selectedMotorization)) {
                        $categories = \Category::getCategoriesTreeByMotorization($selectedMotorization->id, null, $vehicleType);

                        if (!empty($dataObject)) {
                            if (isset($dataObject) && $dataObject instanceOf \Product) {
                                $page = new \Page(\Category::getPageId($_SESSION['lastTecdocPath']['parts_category_id']));
                            } else {
                                $page = $dataObject;
                            }

                            $selectedPath = array((int)$page->id);

                            if ($categories) while ($parentId = $page->parentId) {
                                $selectedPath[] = (int)$parentId;
                                $page = $page->getParent();
                            }
                        }

                        $data['menuBar'][$ident]['children']['categories'] = self::smartyGuideListTecdocPartsCat($categories, 3, $selectedPath);
                        $data['menuBar'][$ident]['children']['selectedPath'] = $selectedPath;
                        $data['menuBar'][$ident]['selectedMotorizationHref'] = $selectedMotorization->url;
                    }
                }
            }
        }

        return $data;
    }

    private static function getSelectedByUrl($pages, $selectedPath)
    {
        $selected = null;
        if ($pages) {
            foreach ($pages as $page) {
                if ($page instanceof \TecdocModelPage || $page instanceof \TecdocMotorizationPage) {
                    if (in_array($page->url, $selectedPath)) $selected = $page;
                } else if (is_array($page)) {
                    if (in_array($page['href'], $selectedPath)) $selected = $page;
                }
            }
        }

        return $selected;
    }

    public static function smartyMenuTecdocItems($items)
    {
        if (empty($items)) return;

        $data = array();
        $i = 0;
        foreach ($items as $row) {

            $data[$i] = array(
                'id'      => $row->id, 
                'href'    => $row->url, 
                'title'   => $row->title, 
                'text'    => $row->title, 
                'target'  => null, 
                'subMenu' => null, 
            );

            if ($row instanceof \TecdocMotorizationPage) {
                $data[$i]['engine_output_kw']   = $row->engine_output_kw;
                $data[$i]['description']        = $row->description;
            }
            
            if ($row instanceof \TecdocModelPage) {
                $data[$i]['year_from']   = $row->year_from;
                $data[$i]['year_to']   = $row->year_to;
            }

            $i++;
        }

        return $data;
    }

    public static function smartyTecdocModelsGuideList($models)
    {
        if (empty($models)) return;

        $data = array();
        $uniques = array();

        $i = 0;
        foreach ($models as $row) {
            $firstWord = '';
            list($firstWord) = explode(' ', trim($row->title));
            if (!in_array($firstWord, $uniques)) {
                $uniques[] = $firstWord;
            }
            $data[$firstWord][] = array(
                'href'      => $row->url, 
                'title'     => $row->title, 
                'heading'   => $row->title, 
                'year_from' => $row->year_from, 
                'year_to'   => $row->year_to,
                'month_from' => $row->month_from, 
                'month_to'   => $row->month_to
            );
            $i++;
        }

        return $data;
    }

    public static function smartyTecdocMotorizationsGuideList($motorizations)
    {
        if (empty($motorizations)) return;
        $memcache = \Reflex::getMemcache();
        $model = new \Model_Tecdoc_KeyTableEntries;
        $sKeyTable = new \Service_Tecdoc_KeyTable($model, $memcache);

        $data = array();
        $uniques = array();

        $i = 0;
        foreach ($motorizations as $row) {
            $data[$i] = array(
                'href'      => $row->url, 
                'title'     => $row->title, 
                'year_from' => $row->model_year_from, 
                'year_to'   => $row->model_year_to, 
                'month_from' => $row->model_month_from, 
                'month_to'   => $row->model_month_to, 
                'engine_capacity_ccm_tech_val' => $row->engine_capacity_ccm_tech_val, 
                'engine_output_kw' => $row->engine_output_kw, 
                'engine_output_hp' => $row->engine_output_hp, 
                'engine_description'    => $row->description,
                'fuel_type'    => $sKeyTable->getInfoByNumberAndEntry ('182', $row->fuel_type),
                'axle_type'    => $sKeyTable->getInfoByNumberAndEntry ('065', $row->axle_config),
                // 'engine_type'    => $model->getInfoByNumberAndEntry ('080', $row->engine_type),

            );
            $i++;
        }

        return $data;
    }

    public static function parseUrlToArray($urlpath)
    {
        $urls = array();
        $urlpath = explode('/', $urlpath);
        if (!empty($urlpath))
        {
            $urlstring = '/';
            foreach ($urlpath as $url)
            {
                if (!empty($url))
                {
                    $urlstring .= $url.'/';
                    $urls[] = $urlstring;
                }
            }
        }
        return $urls;
    }

    public static function smartyMenuRecursive(\Page $parentPage, $level)
    {
        $data = null;
        $level--;
        if ($level < 0) 
            return;

        $sails_img = \Configurator::get('sails') && \Configurator::get('sails_img');
        $menu_icon_img = \Configurator::get('menu_icon_img');

        if ($parentPage->pageTemplate == 'pt_contact')
        {
            $children = $parentPage->getChildren( 1, array('pt_office'), false, $_SESSION['client']->userType );
        }
        else
        {
            $full = ( $sails_img && ( $parentPage->getCategory() or $parentPage->id == \Model_Category::getRsCategoryParent()) );
            $children = $parentPage->getChildren( 1, null, $full, $_SESSION['client']->userType );
        }

        $i = 0;

        foreach ( $children as $item )
        {
            $data[$i] = array(
                'id'      => $item->id,
                'href'    => $item->url,
                'title'   => $item->fullTitle,
                'text'    => $item->menuTitle,
                'target'  => $item->getTarget(),
                'subMenu' => self::smartyMenuRecursive($item, $level),
                'prefered'=> $item->prefered,
            );
            if ( $sails_img && in_array($item->pageTemplate, array("pt_intro", "pt_intro_tecdoc")) ) {
                if ( isset( $item->ff_3_file ) && is_readable( APP_PATH . $item->ff_3_file ) ) {
                    $data[$i]['img_file']   = $item->ff_3_file;
                    $data[$i]['img_title']  = $item->ff_3_title ? $item->ff_3_title : $item->fullTitle;
                } else {
                    $data[$i]['img_file']   = '/css/img/kat-submenu.jpg';
                    $data[$i]['img_title']  = $item->fullTitle;
                }
            }
            if ( $menu_icon_img && in_array($item->pageTemplate, array("pt_intro", "pt_intro_tecdoc", "pt_manufacturer")) );
            {
                if ( isset( $item->ff_4_file ) && is_readable( APP_PATH . $item->ff_4_file ) ) {
                    $data[$i]['icon_file']   = $item->ff_4_file;
                    $data[$i]['icon_title']  = $item->ff_4_title ? $item->ff_4_title : $item->fullTitle;
                }
            }
            $i++;
        }
        return $data;
    }

    public static function smartyMenuItems($pages)
    {
        $data = array();

        if (empty($pages))
            return;

        $i = 0;
        foreach ( $pages as $item )
        {
            
            $data[$i] = array(
                'id'      => $item->id, 
                'href'    => $item->url, 
                'title'   => $item->fullTitle, 
                'text'    => $item->menuTitle, 
                'target'  => $item->getTarget(),
                'subMenu' => null,
            );
            $i++;
        }

        return $data;
    }

    public static function smartyBreadCrumbs($dataObject)
    {
        $data        = array();
        $breadcrumbs = array();

        if ($dataObject instanceof \Product)
        {
            if ($dataObject->isTecdoc()) {
                if (isset($_SESSION['lastTecdocPath']) && !empty($_SESSION['lastTecdocPath'])) {
                    $ok = $dataObject->checkAgainstTecdocAllocations($_SESSION['lastTecdocPath']);

                    if ($ok) {
                        if (isset($_SESSION['lastTecdocPath']['parts_category_id'])) {
                            $siteHierarchy = new \Model_SiteHierarchy();
                            $pageId = $siteHierarchy->findSiteHierarchyId($_SESSION['lastTecdocPath']['parts_category_id']);

                            $data = \Page::getPath($pageId);
                            // $data = self::fixTecdocInactivePages($data);
                        }

                        if (isset($_SESSION['lastTecdocPath']['motorization']) && $_SESSION['lastTecdocPath']['motorization'] instanceof \TecdocMotorizationPage) {
                            $tmp = \TecdocMotorizationPage::getPath($_SESSION['lastTecdocPath']['motorization']->id);
                            $data = array_merge($tmp, $data);
                        }
                    }
                }
            }
            else
            {

                $product_categories = $dataObject->getCategoriesIds();
                $manufacturerId = $dataObject->getManufacturerId();

                $pageId = null;

                if (isset($_SESSION['lastCategory'])) {
                    if ($_SESSION['lastCategory'] instanceOf \Category && in_array($_SESSION['lastCategory']->id, $product_categories)) {
                        $pageId = \Reflex::findPageIdByCatId($_SESSION['lastCategory']->id);
                    } else if ($_SESSION['lastCategory'] instanceOf \Manufacturer && $_SESSION['lastCategory']->id == $manufacturerId) {
                        $pageId = \Reflex::findPageIdByManId($_SESSION['lastCategory']->id);
                    }
                }

                if ( empty($pageId) ) {
                    $pageId = $dataObject->getPrimaryCategory()->id;
                }

                $data = \Page::getPath($pageId);
            }

            array_push(
                $data, 
                array(
                    "url"   => $dataObject->getUrl(),
                    "title" => $dataObject->getTitle(2),
                    "text"  => $dataObject->title, 
                    "isProduct" => 1,
                )
            );
        }
        else
        {
            # kontrola typu stranky
            if ($dataObject instanceof \TecdocMotorizationPage)
            {
                $data = \TecdocMotorizationPage::getPath($dataObject->id);
            }
            else if ($dataObject instanceof \TecdocModelPage)
            {
                $data = \TecdocModelPage::getPath($dataObject->id);
            }
            else
            {
                $data = \Page::getPath($dataObject->id);
                if ( $dataObject->pageTemplate == 'pt_intro_tecdoc' )
                {
                    // $data = self::fixTecdocInactivePages($data);
                }

                if ($dataObject->pageTemplate == 'pt_intro_tecdoc' && isset($_SESSION['tecdoc']['motorization']))
                {
                    $tmp = \TecdocMotorizationPage::getPath($_SESSION['tecdoc']['motorization']->id);
                    $data = array_merge($tmp, $data);
                }
            }
        }

        array_unshift(
            $data, 
            array(
                "url"    => "", 
                "title"  => \SystemPage::instance()->homepage->fullTitle, 
                "text"   => \SystemPage::instance()->homepage->shortTitle
            )
        );

        $urlpath = '';
        foreach ($data as $bread)
        {
            if (isset($bread['isProduct']))
            {
                $urlpath = $bread['url'];
            }
            else
            {
                if (isset($bread['inactive']))
                {
                    
                }
                else
                {
                    $urlpath .= $bread['url'].'/';
                }
            }

            $bread['url'] = $urlpath;
            $breadcrumbs[] = $bread;
        }

        return $breadcrumbs;
    }

    /**
     * @deprecated No longer used by internal code and not recommended.
     * Znepristupnime "medzikategorie" - budu odkazovat na stranku motorizacie
     * Ale zaroven musime doplnit Url poslednej kategorie o Url zneaktivnenych stranok / kategorii
     */
    private static function fixTecdocInactivePages($data)
    {
        $inactiveUrl = '';
        if ($data)
        {
            for ($i = 0; $i < count($data); $i++)
            {
                if ($i < count($data)-1)
                {
                    $data[$i]['inactive'] = 1;
                    $inactiveUrl .= $data[$i]['url'] . '/';
                }
                
                if ($i == count($data)-1)
                {
                    $data[$i]['url'] = $inactiveUrl . $data[$i]['url'];
                }
            }
        }

        return $data;
    }

    public static function smartyGallery($galleryData)
    {
        $data = array();

        if ( !empty($galleryData) )
        {
            $data['title']       = $galleryData['anazev']; 
            $data['description'] = $galleryData['apopis']; 
            if ( !empty($galleryData['photoId']) )
            {
                foreach ( $galleryData['photoId'] as $picture )
                {
                    if ($picture['active'])
                        $data['pictures'][] = array(
                            'fullsize'      => '/img/gallery/'.$galleryData['seo'].'/'.$picture['img'],
                            'thumbnail'     => '/img/gallery/'.$galleryData['seo'].'/small/'.$picture['img'],
                            'title'         => $picture['fnazev'],
                            'description'   => $picture['fpopis'],
                        );

                }
            }

        }

        return $data;
    }

    public static function smartyGuideList($pages, $image_index = 0)
    {
        $data = array();

        if (empty($pages))
            return;

        foreach ( $pages as $page )
        {
            $item               = array();
            $item['heading']    = $page->guidePostTitle ? $page->guidePostTitle : $page->menuTitle;
            $item['title']      = $page->fullTitle;
            $item['href']       = $page->url;
            $item['prefered']   = $page->prefered;


            if ( !$item['heading'] )
                continue;

            // Obrazek
            if ($image_index) {
                $item['image'] = array();
                $imagePath = APP_PATH . $page->{'ff_'.$image_index.'_file'};
                if ($page->{'ff_'.$image_index.'_file'} && is_readable($imagePath)) {
                    $item['image']['src'] = $page->{'ff_'.$image_index.'_file'};
                    $item['image']['alt'] = $page->{'ff_'.$image_index.'_title'};
                }
            }

            $data[] = $item;
        }

        return $data;
    }

    /**
     * Pripravi pole pre vypis kategorii nahradnych dielov TecDoc-u
     * 
     * @param array $categories
     * @param int $level
     * @param array $selectedPath
     * @return mixed
     */
    public static function smartyGuideListTecdocPartsCat($categories, $level, $selectedPath = array())
    {
        $data = array();

        $level--;
        if ($level < 0) 
            return;

        if (empty($categories))
            return;

        $i = 0;
        foreach ($categories as $key => $cat)
        {
            $page = new \Page(\Reflex::findPageIdByCatId($cat['id']), false);

            if (isset($_SESSION['tecdoc']['motorization']) && $_SESSION['tecdoc']['motorization'] instanceof \TecdocMotorizationPage)
            {
                $motorizationUrl = $_SESSION['tecdoc']['motorization']->url;
            }
            else
            {
                $motorizationUrl = $_SESSION['lastTecdocPath']['motorization']->url;
            }

            $item               = array();
            $item['id']         = $page->id;
            $item['text']       = $page->menuTitle;
            $item['title']      = $page->fullTitle;
            if ( isset( $page->ff_5_file ) && is_readable( APP_PATH . $page->ff_5_file ) ) {
                $item['image'] = $page->ff_5_file;
                $item['imageAlt'] = $page->ff_5_title;
            }
            $item['href']       = substr($motorizationUrl, 0, -1) . $page->url;
            $item['inSelectedPath'] = (in_array($item['id'], $selectedPath)) ? 1 : 0;
            $item['prefered']   = $page->prefered;


            if (isset($cat['children'])) {
                $item['children'] = self::smartyGuideListTecdocPartsCat($cat['children'], $level, $selectedPath);
            }

            if ( isset( $page->ff_4_file ) && is_readable( APP_PATH . $page->ff_4_file ) ) {
                $item['icon_file']   = $page->ff_4_file;
                $item['icon_title']  = $page->ff_4_title ? $page->ff_4_title : $page->fullTitle;
            }

            if ( !$item['text'] )
                continue;

            // LL: kategorie nesmi byt zakazana
            if ($page->enabled==1) $data[] = $item;
            $i++;
        }

        return $data;
    }

    public static function smartyGuidePost($pages, $fields = array('field_index' =>'5', 'image_index' => 1 ))
    {
        $data = array();

        if (empty($pages))
            return;

        foreach ( $pages as $page )
        {
            $item = array();

            if (!empty($page->df_1)) {
                if ($page->df_1 instanceof \DibiDateTime) {
                    $item['date'] = $page->df_1->format('d. m. Y');
                } else if ($page->df_1 != '0000-00-00') {
                    $date = \DateTime::createFromFormat('Y-m-d', $page->df_1);
                    $item['date'] = $date->format('d. m. Y');
                }
            }

            $item['title']   = $page->fullTitle;
            $item['heading'] = $page->guidePostTitle ? $page->guidePostTitle : $page->menuTitle;
            $item['href']    = $page->url;
            $item['text']    = $page->{'ha_'.$fields['field_index']};
            $item['image']   = array();

            $fileUrl  = $page->{'ff_'.$fields['image_index'].'_file'};
            $filePath = ".{$fileUrl}";

            if ( $fileUrl && is_file($filePath) )
            {
                $item['image']['title'] = $page->{'ff_'.$fields['image_index'].'_title'};
                $item['image']['url']   = $fileUrl;
            }

            if ( !$item['title'] )
                continue;

            $data[] = $item;
        }

        return $data;

    }

    public static function smartyGuidePostFlyers($pages, $fields = array('field_index' =>'3', 'image_index' => 1 ))
    {
        $data = array();

        if (empty($pages))
            return;

        foreach ($pages as $page)
        {
            $item = array();

            $item['href']       = $page->url;
            $item['title']      = $page->fullTitle;
            $item['heading']    = $page->menuTitle;
            $item['target']     = $page->getTarget();
            $item['text']       = $page->{'ha_'.$fields['field_index']};

            // anotacny obrazok letaku
            $fileUrl  = $page->{'ff_'.$fields['image_index'].'_file'};
            $filePath = ".{$fileUrl}";

            if ( $fileUrl && is_file($filePath) ) {
                $item['image']['title'] = $page->{'ff_'.$fields['image_index'].'_title'};
                $item['image']['url']   = $fileUrl;
            }

            // letak
            $fileUrl  = $page->p_1_file;
            $filePath = ".{$fileUrl}";

            if ( $fileUrl && is_file($filePath) )
            {
                $item['flyer']['title'] = $page->p_1_title;
                $item['flyer']['url']   = $fileUrl;
            }

            $data[] = $item;
        }

        return $data;
    }

    /**
     * Pobocky
     */
    public static function smartyOffices($offices)
    {
        $data = array();

        if (empty($offices)) {
            return;
        }

        foreach ($offices as $office)
        {   

            $heading = $office->fullTitle ? $office->fullTitle : $office->menuTitle;
            $item = array(
                    'title'     => $office->fullTitle,
                    'heading'   => $heading,
                    'text'      => $office->ha_4
            );

            if ($office->ff_3_file) {
                $item['image']      = $office->ff_3_file;
                $item['imageTitle'] = $office->ff_3_title;
            }

            if ($office->fullTitle) $item["h1"] = $office->fullTitle;
            if ($office->tf_1) $item["street"]  = $office->tf_1;
            if ($office->tf_2) $item["city"]    = $office->tf_2;

            if (is_numeric($office->tf_3)) {
                $office->tf_3 = round($office->tf_3);
            }

            if ($office->tf_3) $item["postcode"]        = $office->tf_3;
            if ($office->tf_4) $item["country"]         = $office->tf_4;

            if ($office->tf_5) {
                $item["email"] = hexadecimalEncode($office->tf_5);
                $item["mailtoemail"] = hexadecimalEncode("mailto:") . $item['email'];
            }

            if ($office->tf_6) {
                $item["email2"] = hexadecimalEncode($office->tf_6);
                $item["mailtoemail2"] = hexadecimalEncode("mailto:") . $item['email2'];
            }

            if ($office->tf_7) $item["phone"]           = $office->tf_7;
            if ($office->tf_8) $item["phone2"]          = $office->tf_8;
            if ($office->tf_9) $item["fax"]             = $office->tf_9;
            if ($office->tf_10) {
                $item["webpage"] = $office->tf_10;
                $item['protocol'] = \Env::getInstance()->protocol;
                $webpage = preg_split('/(http[s]{0,1}:\/\/)/', $item['webpage'], -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                if (count($webpage) > 1) {
                    $item['protocol'] = $webpage[0];
                    $item['webpage'] = $webpage[1];
                }
            }
            if ($office->tf_11) $item["facebookUrl"]    = $office->tf_11;
            if ($office->ha_1) $item["openinghours"]    = $office->ha_1;
            if (\Configurator::get('contact_map')) {
                $item['map']['code'] = $office->hat_1;
                $item['map']['image'] = array(
                    'title' => $office->ff_1_title,
                    'url'   => $office->ff_1_file
                );
            }
            $item['href']               = $office->url;

            $data[] = $item;
        }
        
        return $data;
    }

    /**
     * Pozice ve firme
     */
    public static function smartyDepartments($departments)
    {
        $data = array();

        if (empty($departments)) {
            return;
        }

        foreach ($departments as $i => $department)
        {
            $persons = $department->getChildren(1, array('pt_contact_person'), true, $_SESSION['client']->userType);
            if (!empty($persons))
            {
                $data[$i]['department']  = $department->tf_1;
                $data[$i]['persons']     = array();
                $data[$i]["persons"] = self::smartyPersons($persons);
            }
        }

        return $data;
    }

    /**
     * Kontaktne osoby
     */
    public static function smartyPersons($persons)
    {
        $data = array();

        if (empty($persons)) {
            return;
        }

        foreach ($persons as $person)
        {
            $contactPerson = array();
            
            // obsah
            if ($person->tf_1) $contactPerson["name"]           = $person->tf_1;
            if ($person->tf_2) $contactPerson["post"]           = $person->tf_2;
            if ($person->tf_3) $contactPerson["mobilephone"]    = $person->tf_3;
            if ($person->tf_4) $contactPerson["phone"]          = $person->tf_4;
            if ($person->tf_5) $contactPerson["fax"]            = $person->tf_5;

            if ($person->tf_6) {
                $contactPerson["email"]       = hexadecimalEncode($person->tf_6);
                $contactPerson["mailtoemail"] = hexadecimalEncode("mailto:") . $contactPerson['email'];
            }

            if ($person->ha_1) $contactPerson["note"]           = $person->ha_1;
            
            // fotka
            $fileUrl  = $person->ff_1_file;
            $filePath = ".{$fileUrl}";

            if ($fileUrl && is_file($filePath))
            {
                $contactPerson['image']          = array();
                $contactPerson['image']['title'] = $person->ff_1_title;
                $contactPerson['image']['url']   = $fileUrl;
            }

            $data[] = $contactPerson;
        }

        return $data;
    }

    public static function prepareDataToColumns($data, $columnsCount = 4)
    {
        $filterItemCount = count($data);
        $rowsCount = ceil($filterItemCount / $columnsCount); //pocet prvku ve sloupci (resp. pocet radku)
        $fullColumn = $filterItemCount % $columnsCount; // pocet plnych sloupcu

        reset($data); 
        $index = 0;

        for( $i = 0; $i < $fullColumn; $i++)
        {
            for ($u = 0; $u < $rowsCount; $u++)
            {
                $newdata[$index][key($data)] = current($data);
                next($data);
            }
            $index++;
        }
        if ($fullColumn) $rowsCount -= 1;

        for( $i = $fullColumn; $i < $columnsCount; $i++)
        {
            for ($u = 0; $u < $rowsCount; $u++)
            {
                $newdata[$index][key($data)] = current($data);
                next($data);
            }
            $index++;

        }
        return $newdata;
        
    }

    /**
     * PDF letak.
     */
    public static function pdfBooklet($bookletUrl, $bookletTitle)
    {
        $bookletPath = ".{$bookletUrl}";
        $folderUrl   = self::folderFromPath($bookletUrl);
        $folderPath  = self::folderFromPath($bookletPath);

        if ( !$bookletUrl || !is_file($bookletPath) )
            return;

        $data           = array();
        $data['url']    = $bookletUrl;
        $data['file']   = basename($bookletPath);
        $data['title']  = $bookletTitle;
        $data['images'] = array();

        $pattern = $folderPath . '/booklet*.jpg';
        $files   = glob($pattern);
        natsort($files);

        foreach ( $files as $fullFilePath )
        {
            $fileName = basename($fullFilePath);
            $fileUrl  = $folderUrl . '/' . $fileName;

            $item        = array();
            $item['url'] = $fileUrl;

            $data['images'][] = $item;
        }

        return $data;
    }


    protected static function folderFromPath($filePath)
    {
        $parts = explode('.', $filePath);
        array_pop($parts);
        $convertDirectory = join('.', $parts);

        return $convertDirectory;
    }

    public static function smartyEnginesForSearch ($engines)
    {
        $data = array();
        
        foreach ($engines as $engine)
        {
            $_engine = array(
                'id' => $engine->id,
                'manufacturer' => $engine->mtitle,
                'manufacturerId' => $engine->manufacturerId,
                'description' => $engine->description,
                'ccm_tech_from' => $engine->capacity_ccm_tech_from,
                'ccm_tech_to' => $engine->capacity_ccm_tech_to,
                'output_kw_from' => $engine->output_kw_from,
                'output_kw_to' => $engine->output_kw_to,
                'output_hp_from' => $engine->output_hp_from,
                'output_hp_to' => $engine->output_hp_to,
                'cylinders' => $engine->cylinders,
            );
            $_engine['motorizations'] = self::smartyMotorizationsForSearch($engine->motorizations);
            $data[] = $_engine;
        }
        
        return $data;
    }
    
    public static function smartyMotorizationsForSearch ($motorizations)
    {
        $memcache = \Reflex::getMemcache();
        $model = new \Model_Tecdoc_KeyTableEntries;
        $sKeyTable = new \Service_Tecdoc_KeyTable($model, $memcache);

        $data = array();
        
        foreach ($motorizations as $motorization)
        {
            $model = new \TecdocModelPage($motorization->modelId, array(), true, $motorization->PC == 1 ? 1 : 2);
            $_motorization = array(
                'id' => $motorization->id,
                'url' => $model->url . $motorization->seo,
                'full_title' => $motorization->matitle . " " . $motorization->motitle . " " . $motorization->title,
                'ccm' => $motorization->engine_capacity_ccm_tech_val,
                'output_kw' => $motorization->engine_output_kw,
                'output_hp' => $motorization->engine_output_hp,
                'cylinders' => $motorization->cylinders,
                'year_from' => $motorization->model_year_from, 
                'year_to'   => $motorization->model_year_to, 
                'month_from' => $motorization->model_month_from, 
                'month_to'   => $motorization->model_month_to, 
            );
            if ($motorization->PC == 1) {
                $_motorization['body_type'] = $sKeyTable->getInfoByNumberAndEntry ('086', $motorization->body_type);
            } else if ($motorization->CV == 1) {
                $_motorization['body_type'] = $sKeyTable->getInfoByNumberAndEntry ('067', $motorization->body_type);
            }
            $data[] = $_motorization;
        }
        
        return $data;
    }

}
