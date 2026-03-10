<?php
$_['heading_title']               = 'OC Перейменувати Admin';
$_['error_permission']            = 'Увага: Ви не маєте прав для зміни модуля AdminRename!';
$_['error_validation']            = 'Увага: Нова папка admin може містити лише цифри, букви, підкреслення або тире';
$_['error_pathreplaces_backup']   = 'Увага: Створення резервної копії pathReplaces.php не вдалося';
$_['error_pathreplaces_update']   = 'Увага: Оновлення pathReplaces.php не вдалося. Можливо, є проблема з дозволом';
$_['error_target_exists']         = 'Увага: Цільова папка вже існує';
$_['error_admin_rename']          = 'Увага: Перейменування папки admin не вдалося. Можливо, є проблема з дозволом';
$_['error_config_backup']         = 'Увага: Створення резервної копії config.php не вдалося';
$_['error_config_update']         = 'Увага: Оновлення config.php не вдалося. Можливо, є проблема з дозволом';
$_['text_success']                = 'Успіх: Ви змінили модуль AdminRename!';
$_['text_rename_success']         = 'Папку admin успішно перейменовано на %s';
$_['text_enabled']                = 'Увімкнено';
$_['text_disabled']               = 'Вимкнено';
$_['button_cancel']			      = 'Скасувати';
$_['save_changes']			      = 'Зберегти зміни';
$_['text_rename']				  = 'Перейменувати';
$_['text_default']				  = 'За замовчуванням';
$_['text_module']				  = 'Модуль';
// Панель управління
$_['entry_code']                  = 'Статус AdminRename: ';
$_['entry_code_help'] 			  = 'Увімкніть або вимкніть модуль';
$_['text_content_top']			  = 'Верхній контент';
$_['text_content_bottom']		  = 'Нижній контент';
$_['text_column_left']		      = 'Ліва колонка';
$_['text_column_right']		      = 'Права колонка';
$_['entry_layout']        		  = 'Макет:';
$_['entry_position']      		  = 'Позиція:';
$_['entry_status']        		  = 'Статус:';
$_['entry_sort_order']    		  = 'Порядок сортування:';
$_['entry_action_options']		  = 'Дії:';
$_['entry_layout_options']        = 'Опції макета:';
$_['entry_position_options']      = 'Опції позиції:';
$_['button_add_module'] 		  = 'Додати модуль';
$_['button_remove']			      = 'Видалити';
$_['text_rename_your_dir']        = 'Перейменувати папку admin';
$_['text_current_admin_folder']   = 'Поточна назва папки admin:';
$_['text_desired_admin_folder_name']  = 'Бажана назва папки admin:';
$_['text_what_happens_on_backend'] = 'Що відбувається на задньому плані при перейменуванні?';
$_['desc_what_happens_on_backend'] =
  '<li>Спочатку модуль створює резервну копію вашого поточного файлу config.php для адмінки</li>
<li>Редагується файл config.php</li>
<li>Папка admin перейменовується</li>';
$_['text_note_what_happens_on_backend'] = '<p><strong>Примітка*</strong>&nbsp;Якщо будь-який з етапів описаних вище не вдасться, наприклад, якщо виникне проблема з дозволом, то внесені до моменту помилки зміни будуть скасовані</p>';
// Користувацький CSS
$_['custom_css']				  = 'Користувацький CSS:';
$_['custom_css_help'] 		      = 'Введіть тут свої стилі.';
$_['custom_css_placeholder']	  = 'Введіть свій CSS тут...';
// Залежність модуля
$_['wrap_widget']		 	 	  = 'Обгорнути у віджет:';
$_['wrap_widget_help'] 	   		  = 'Якщо увімкнено, модуль буде показано у вигляді віджета. Ця функція залежить від використовуваної теми.';
$_['text_products']		 	      = 'Продукти:';
$_['text_products_help'] 	      = 'Вкажіть кількість продуктів для відображення.';
$_['text_image_dimensions']	      = 'Розміри зображення продукту:';
$_['text_image_dimensions_help']  = 'Вкажіть ширину та висоту зображення продукту у пікселях.';
$_['text_pixels']				  = 'пікселів';
$_['text_products_small']		  = 'продуктів';
$_['text_panel_name'] 			  = 'Назва панелі:';
$_['text_panel_name_help']		  = 'Виберіть назву панелі. Підтримка багатомовності.';
$_['show_add_to_cart'] 			  = 'Показати кнопку "Додати до кошика":';
$_['show_add_to_cart_help']	      = 'Увімкніть/вимкніть показ кнопки Додати до кошика для відображених продуктів';

// Вкладка підтримки
$_['text_your_license'] = 'Ваша ліцензія';
$_['text_please_enter_the_code'] = "Будь ласка, введіть код ліцензії на придбання продукту";
$_['text_activate_license'] = "Активувати ліцензію";
$_['text_not_having_a_license'] = "Немає коду? Отримайте його тут.";
$_['text_license_holder'] = "Власник ліцензії";
$_['text_registered_domains'] = "Зареєстровані домени";
$_['text_expires_on'] = "Термін дії ліцензії закінчується";
$_['text_valid_license'] = "ДІЙСНА ЛІЦЕНЗІЯ";
$_['text_get_support'] = 'Отримати підтримку';
$_['text_community'] = "Спільнота";
$_['text_ask_our_community'] = "Запитайте спільноту про вашу проблему на форумі iSenseLabs.";
$_['text_tickets'] = 'Тікети';
$_['text_open_a_ticket'] = 'Бажаєте поспілкуватися один-на-один з нашими технічними спеціалістами? Тоді відкрийте тікет підтримки.';
$_['text_pre_sale'] = 'Передпродажна консультація';
$_['text_pre_sale_desc'] = 'Є чудова ідея для вашого інтернет-магазину? Наша команда професійних розробників допоможе реалізувати її.';
$_['text_browse_forums'] = 'Переглянути форуми';
$_['text_open_ticket_for_real'] = 'Відкрити тікет підтримки';
$_['text_bump_the_sales'] = 'Підвищити продажі';
?>
