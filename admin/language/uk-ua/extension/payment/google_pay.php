<?php
// Заголовок
$_['heading_title']					 	= 'Google Pay';

// Текст
$_['text_google_pay']				 	= '<img src="view/image/payment/googlepay.jpg" height="40" alt="Google Pay" title="Google Pay" style="border: 0px;" />';
$_['text_extensions']			 	 	= 'Розширення';
$_['text_edit']			 	 		 	= 'Редагувати Google Pay';
$_['text_general']				 	 	= 'Основне';
$_['text_gateway']					 	= 'Шлюз';
$_['text_advanced']					 	= 'Додаткові налаштування';
$_['text_button']					 	= 'Кнопка';
$_['text_production']			 	 	= 'Продакшн / Активний';
$_['text_sandbox']		 		 		= 'Пісочниця';
$_['text_test']			 			 	= 'Тестовий режим';
$_['text_default']			 		 	= 'За замовчуванням';
$_['text_black']			 		 	= 'Чорний';
$_['text_white']			 			= 'Білий';
$_['text_long']			 		 	 	= 'Довга - "Купити з Google Pay"';
$_['text_short']			 		 	= 'Коротка - "Google Pay"';
$_['text_card_amex']			 	 	= 'American Express';
$_['text_card_discover']			 	= 'Discover';
$_['text_card_jcb']			 	 	 	= 'JCB';
$_['text_card_mastercard']			 	= 'Mastercard';
$_['text_card_visa']			 	 	= 'Visa';
$_['text_pan_only']			 	 	 	= 'Оплата карткою';
$_['text_cryptogram_3ds']			 	= 'Токени пристроїв Android';
$_['text_example_gateway']			 	= 'Приклад шлюзу';
$_['text_example_gateway_merchant_id']	= 'ID торговця прикладу шлюзу';
$_['text_braintree']			 	 	= 'Braintree';
$_['text_braintree_api_version']	 	= 'Версія API Braintree';
$_['text_braintree_sdk_version']	 	= 'Версія SDK Braintree';
$_['text_braintree_environment']	 	= 'Середовище Braintree';
$_['text_braintree_merchant_id']	 	= 'ID торговця Braintree';
$_['text_braintree_public_key']	 		= 'Публічний ключ Braintree';
$_['text_braintree_private_key']	 	= 'Приватний ключ Braintree';
$_['text_braintree_tokenization_key']	= 'Ключ токенізації Braintree';
$_['text_globalpayments']			 	= 'Global Payments';
$_['text_globalpayments_merchant_id']	= 'ID торговця Global Payments';
$_['text_globalpayments_shared_secret']	= 'Спільний секрет Global Payments';
$_['text_globalpayments_environment']	= 'Середовище Global Payments';

// Параметри
$_['entry_merchant_id']			 		= 'Google ID торговця';
$_['entry_merchant_name']			 	= 'Назва торговця Google';
$_['entry_total']					 	= 'Загальна сума';
$_['entry_order_status'] 				= 'Статус замовлення';
$_['entry_geo_zone']				 	= 'Геозона';
$_['entry_status']					 	= 'Статус';
$_['entry_sort_order']				 	= 'Порядок сортування';
$_['entry_debug']					 	= 'Журнал налагодження';
$_['entry_environment']				 	= 'Середовище';
$_['entry_merchant_gateway']	 	 	= 'Шлюз торговця';
$_['entry_card_networks']	 	 	 	= 'Підтримувані типи карток';
$_['entry_auth_methods']	 	 	 	= 'Методи авторизації';
$_['entry_accept_prepay_cards']	 	 	= 'Приймати передплачені картки';
$_['entry_shipping_require_phone']	 	= 'Вимагати номер телефону для доставки';
$_['entry_shipping_country_limit'] 	 	= 'Обмеження країн для доставки';
$_['entry_shipping_country_list'] 	 	= 'Дозволені країни';
$_['entry_billing_require_phone']	 	= 'Вимагати номер телефону для рахунку';
$_['entry_button_color']	 	 	 	= 'Колір кнопки';
$_['entry_button_type']	 	 	 		= 'Тип кнопки';

// Підказки
$_['help_merchant_id']		 	 		= 'Це повинен бути ваш ID торговця, який використовується у робочому середовищі. Лише UTF-8 символи.';
$_['help_merchant_name']		 	 	= 'Це має бути назва вашого торговця, яка може використовуватися для підтримки клієнтів або відображатися у сповіщеннях про транзакції. Лише UTF-8 символи.';
$_['help_debug']					 	= 'Увімкнення журналу налагодження запише чутливі дані у файл журналу та консоль браузера. НЕ вмикайте, якщо ви не впевнені у необхідності.';
$_['help_total']					 	= 'Загальна сума замовлення, яку потрібно досягти, щоб цей метод оплати став активним.';
$_['help_auth_methods']		 	 	 	= 'Оплата карткою надасть користувачеві збережені картки з Google облікового запису, токени пристроїв Android можуть не підтримуватися всіма шлюзами.';
$_['help_shipping_restriction']		 	= 'За замовчуванням доставка дозволена в усі країни, або можна обрати, до яких країн дозволена доставка.';

// Успіх
$_['success_save']		 			 	= 'Успіх: Ви змінили дані облікового запису Google Pay!';

// Помилка
$_['error_permission']	 				= 'Попередження: У вас немає дозволу на зміну налаштувань оплати Google Pay!';
$_['error_warning']          		 	= 'Попередження: Будь ласка, уважно перевірте форму на наявність помилок!';
$_['error_merchant_id']		 	 		= 'Невірний ID торговця, має містити більше 3 символів і менше 50';
$_['error_merchant_name']		 	 	= 'Невірна назва торговця, має містити більше 3 символів і менше 50';
$_['error_card_networks_code']		 	= 'Не обрано жодного типу картки, потрібно вибрати хоча б одну.';
$_['error_auth_methods_code']		 	= 'Не обрано метод авторизації, потрібно вибрати хоча б один. Оплата карткою повинна бути обов\'язково обрана.';
