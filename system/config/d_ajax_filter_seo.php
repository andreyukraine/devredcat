<?php
$_['d_ajax_filter_seo_setting'] = array(
    'generate_status' => 0,
    'sheet' => array(
        'ajax_filter_seo' => array(
            'code' => 'ajax_filter_seo',
            'name' => 'text_ajax_filter_seo',
            'unique_url' => true,
            'exception_data' => 'sort, order, page, limit, codename, secret, bfilter, ajaxfilter, edit, gclid, utm_source, utm_medium, utm_campaign',
            'short_url' => false,
        ),
    ),
);

$_['d_ajax_filter_seo_field_setting'] = array(
    'sheet' => array(
        'ajax_filter_seo' => array(
            'code' => 'ajax_filter_seo',
            'name' => 'text_ajax_filter_seo',
            'icon' => 'fa-filter',
            'sort_order' => '11',
            'field' => array(
                'title' => array(
                    'code' => 'title',
                    'name' => 'text_query_title',
                    'description' => '',
                    'type' => 'text',
                    'sort_order' => '1',
                    'multi_store' => true,
                    'multi_language' => true,
                    'multi_store_status' => false,
                    'required' => true
                ),
                'description' => array(
                    'code' => 'description',
                    'name' => 'text_description',
                    'description' => '',
                    'type' => 'textarea',
                    'sort_order' => '3',
                    'multi_store' => true,
                    'multi_language' => true,
                    'multi_store_status' => false,
                    'required' => false
                ),
                'meta_title' => array(
                    'code' => 'meta_title',
                    'name' => 'text_meta_title',
                    'description' => '',
                    'type' => 'text',
                    'sort_order' => '4',
                    'multi_store' => true,
                    'multi_language' => true,
                    'multi_store_status' => false,
                    'required' => true
                ),
                'meta_description' => array(
                    'code' => 'meta_description',
                    'name' => 'text_meta_description',
                    'description' => '',
                    'type' => 'textarea',
                    'sort_order' => '5',
                    'multi_store' => true,
                    'multi_language' => true,
                    'multi_store_status' => false,
                    'required' => false
                ),
                'meta_keyword' => array(
                    'code' => 'meta_keyword',
                    'name' => 'text_meta_keyword',
                    'description' => '',
                    'type' => 'textarea',
                    'sort_order' => '6',
                    'multi_store' => true,
                    'multi_language' => true,
                    'multi_store_status' => false,
                    'required' => false
                ),
                'meta_robots' => array(
                    'code' => 'meta_robots',
                    'name' => 'text_meta_robots',
                    'description' => 'help_post_meta_robots',
                    'type' => 'select',
                    'option' => array(
                        '0' => array(
                            'code' => 'index,follow',
                            'name' => 'index,follow'
                        ),
                        '1' => array(
                            'code' => 'noindex,follow',
                            'name' => 'noindex,follow'
                        ),
                        '2' => array(
                            'code' => 'index,nofollow',
                            'name' => 'index,nofollow'
                        ),
                        '3' => array(
                            'code' => 'noindex,nofollow',
                            'name' => 'noindex,nofollow'
                        )
                    ),
                    'sort_order' => '14',
                    'multi_store' => true,
                    'multi_language' => true,
                    'multi_store_status' => false,
                    'required' => false
                ),
                'target_keyword' => array(
                    'code' => 'target_keyword',
                    'name' => 'text_target_keyword',
                    'description' => 'help_target_keyword',
                    'type' => 'textarea',
                    'sort_order' => '20',
                    'multi_store' => true,
                    'multi_language' => true,
                    'multi_store_status' => false,
                    'required' => false
                ),
                'url_keyword' => array(
                    'code' => 'url_keyword',
                    'name' => 'text_url_keyword',
                    'description' => 'help_url_keyword',
                    'type' => 'text',
                    'sort_order' => '31',
                    'multi_store' => true,
                    'multi_language' => true,
                    'multi_store_status' => false,
                    'required' => false
                ),
                'seo_rating' => array(
                    'code' => 'seo_rating',
                    'name' => 'text_seo_rating',
                    'description' => 'help_seo_rating',
                    'type' => 'info',
                    'sort_order' => '50',
                    'multi_store' => true,
                    'multi_language' => true,
                    'multi_store_status' => false,
                    'required' => false
                )
            )
        )
    )
);

$_['d_ajax_filter_seo_url_generator_setting'] = array(
    'sheet' => array(
        'ajax_filter_seo' => array(
            'code' => 'ajax_filter_seo',
            'name' => 'text_ajax_filter_seo',
            'icon' => 'fa-filter',
            'sort_order' => '10',
            'field' => array(
                'url_keyword' => array(
                    'code' => 'url_keyword',
                    'name' => 'text_url_keyword',
                    'description' => 'help_generate_ajax_filter_seo_url_keyword',
                    'sort_order' => '1',
                    'template_default' => '[title]',
                    'template_button' => array('[title]', '[target_keyword]'),
                    'multi_language' => true,
                    'translit_language_symbol_status' => false,
                    'transform_language_symbol_id' => '1',
                    'overwrite' => false,
                    'button_generate' => true,
                    'button_clear' => true
                )
            ),
            'button_popup' => array(
                '[target_keyword]' => array(
                    'code' => '[target_keyword]',
                    'name' => 'text_insert_target_keyword',
                    'field' => array(
                        'number' => array(
                            'code' => 'number',
                            'name' => 'text_keyword_number',
                            'type' => 'text',
                            'value' => '1'
                        )
                    )
                )
            )
        ),
    )
);

$_['d_ajax_filter_seo_meta_generator_setting'] = array(
    'sheet' => array(
        'ajax_filter_seo' => array(
            'code' => 'ajax_filter_seo',
            'name' => 'text_ajax_filter_seo',
            'icon' => 'fa-filter',
            'sort_order' => '10',
            'field' => array(
                'meta_title' => array(
                    'code' => 'meta_title',
                    'name' => 'text_meta_title',
                    'description' => 'help_generate_ajax_filter_seo_meta_title',
                    'sort_order' => '1',
                    'template_default' => '[title]',
                    'template_button' => array('[title]', '[description]', '[target_keyword]', '[store_name]', '[store_title]'),
                    'multi_language' => true,
                    'translit_symbol_status' => false,
                    'translit_language_symbol_status' => false,
                    'transform_language_symbol_id' => '0',
                    'overwrite' => false,
                    'button_generate' => true,
                    'button_clear' => true
                ),
                'meta_description' => array(
                    'code' => 'meta_description',
                    'name' => 'text_meta_description',
                    'description' => 'help_generate_ajax_filter_seo_meta_description',
                    'sort_order' => '2',
                    'template_default' => '[title] - [description#sentences=1]',
                    'template_button' => array('[title]', '[description]', '[target_keyword]', '[store_name]', '[store_title]'),
                    'multi_language' => true,
                    'translit_symbol_status' => false,
                    'translit_language_symbol_status' => false,
                    'transform_language_symbol_id' => '0',
                    'overwrite' => false,
                    'button_generate' => true,
                    'button_clear' => true
                ),
                'meta_keyword' => array(
                    'code' => 'meta_keyword',
                    'name' => 'text_meta_keyword',
                    'description' => 'help_generate_ajax_filter_seo_meta_keyword',
                    'sort_order' => '3',
                    'template_default' => '[title]',
                    'template_button' => array('[title]', '[description]', '[target_keyword]', '[store_name]', '[store_title]'),
                    'multi_language' => true,
                    'translit_symbol_status' => false,
                    'translit_language_symbol_status' => false,
                    'transform_language_symbol_id' => '0',
                    'overwrite' => false,
                    'button_generate' => true,
                    'button_clear' => true
                )
            ),
            'button_popup' => array(
                '[description]' => array(
                    'code' => '[description]',
                    'name' => 'text_insert_description',
                    'field' => array(
                        'sentences' => array(
                            'code' => 'sentences',
                            'name' => 'text_sentence_total',
                            'type' => 'text',
                            'value' => '1'
                        )
                    )
                ),
                '[target_keyword]' => array(
                    'code' => '[target_keyword]',
                    'name' => 'text_insert_target_keyword',
                    'field' => array(
                        'number' => array(
                            'code' => 'number',
                            'name' => 'text_keyword_number',
                            'type' => 'text',
                            'value' => '1'
                        )
                    )
                )
            )
        )
    )
);

$_['d_ajax_filter_seo_manager_setting'] = array(
    'sheet' => array(
        'ajax_filter_seo' => array(
            'code' => 'ajax_filter_seo',
            'name' => 'text_ajax_filter_seo',
            'icon' => 'fa-filter',
            'sort_order' => '10',
            'field_index' => 'query_id',
            'field' => array(
                'query_id' => array(
                    'code' => 'query_id',
                    'name' => 'text_query_id',
                    'type' => 'link',
                    'sort_order' => '1',
                    'multi_store' => false,
                    'multi_language' => false,
                    'list_status' => true,
                    'export_status' => true,
                    'required' => true
                ),
                'title' => array(
                    'code' => 'title',
                    'name' => 'text_query_title',
                    'type' => 'text',
                    'sort_order' => '2',
                    'multi_store' => true,
                    'multi_language' => true,
                    'list_status' => true,
                    'export_status' => true,
                    'required' => false
                ),
                'description' => array(
                    'code' => 'description',
                    'name' => 'text_description',
                    'type' => 'textarea',
                    'sort_order' => '4',
                    'multi_store' => true,
                    'multi_language' => true,
                    'list_status' => false,
                    'export_status' => true,
                    'required' => false
                ),
                'meta_title' => array(
                    'code' => 'meta_title',
                    'name' => 'text_meta_title',
                    'type' => 'text',
                    'sort_order' => '5',
                    'multi_store' => true,
                    'multi_language' => true,
                    'list_status' => true,
                    'export_status' => true,
                    'required' => false
                ),
                'meta_description' => array(
                    'code' => 'meta_description',
                    'name' => 'text_meta_description',
                    'type' => 'textarea',
                    'sort_order' => '6',
                    'multi_store' => true,
                    'multi_language' => true,
                    'list_status' => true,
                    'export_status' => true,
                    'required' => false
                ),
                'meta_keyword' => array(
                    'code' => 'meta_keyword',
                    'name' => 'text_meta_keyword',
                    'type' => 'textarea',
                    'sort_order' => '7',
                    'multi_store' => true,
                    'multi_language' => true,
                    'list_status' => true,
                    'export_status' => true,
                    'required' => false
                ),
                'meta_robots' => array(
                    'code' => 'meta_robots',
                    'name' => 'text_meta_robots',
                    'type' => 'select',
                    'option' => array(
                        '0' => array(
                            'code' => 'index,follow',
                            'name' => 'index,follow'
                        ),
                        '1' => array(
                            'code' => 'noindex,follow',
                            'name' => 'noindex,follow'
                        ),
                        '2' => array(
                            'code' => 'index,nofollow',
                            'name' => 'index,nofollow'
                        ),
                        '3' => array(
                            'code' => 'noindex,nofollow',
                            'name' => 'noindex,nofollow'
                        )
                    ),
                    'sort_order' => '14',
                    'multi_store' => true,
                    'multi_language' => true,
                    'list_status' => false,
                    'export_status' => true,
                    'required' => false
                ),
                'target_keyword' => array(
                    'code' => 'target_keyword',
                    'name' => 'text_target_keyword',
                    'type' => 'textarea',
                    'sort_order' => '20',
                    'multi_store' => true,
                    'multi_language' => true,
                    'list_status' => true,
                    'export_status' => true,
                    'required' => false
                ),
                'url_keyword' => array(
                    'code' => 'url_keyword',
                    'name' => 'text_url_keyword',
                    'type' => 'text',
                    'sort_order' => '30',
                    'multi_store' => true,
                    'multi_language' => true,
                    'list_status' => true,
                    'export_status' => true,
                    'required' => false
                )
            )
        )
    )
);
