<?php if (($moduleData['Enabled'] != 'no') && ($ShowPage==true)) { ?>
    <span class="PB_heading_text"><?php echo $text_option_heading; ?></span>  
    <?php if ($products) { ?>
        <form method="post" id="ProductBundlesOptionsForm">
        <?php $i=0; $max = sizeof($products); ?>
        <?php foreach ($products as $product) { ?>
            <div data-product-index="<?php echo $i; ?>" class="<?php if (($i+1) == $max) echo "PB_options_product_item_last"; else echo "PB_options_product_item"; ?>">
                <?php if ($product['thumb']) { ?>
                    <div class="PB_image"><a href="<?php echo $product['href']; ?>"><img class="PB_options_image" src="<?php echo $product['thumb']; ?>" alt="<?php echo $product['name']; ?>" /></a></div>
                <?php } ?>
                <?php if ($product['price']) { ?>
                    <?php if (!$product['special']) { ?>
                        <?php $Pprice = $product['price']; ?>
                    <?php } else { ?>
                        <?php $Pprice = $product['special']; ?>
                    <?php } ?>
                <?php } ?>
                <div class="PB_options_product_field">
                    <a href="<?php echo $product['href']; ?>"><?php echo $product['name']; ?></a>
                    <strong><?php echo $Pprice; ?></strong>
                </div>
                <?php if ($product['options']) { ?>
                    <div class="PB_options">
                        <?php foreach ($product['options'] as $option) { ?>
                            <?php if ($option['type'] == 'select') { ?>
                                <div data-option-id="bundle_option-<?php echo $option['product_option_id']; ?>" class="option">
                                    <?php if ($option['required']) { ?>
                                        <span class="required">*</span>
                                    <?php } ?>
                                    <b><?php echo $option['name']; ?>:</b><br />
                                    <select name="option[<?php echo $i; ?>][<?php echo $option['product_option_id']; ?>]" class="form-control">
                                        <option value=""><?php echo $text_select; ?></option>
                                        <?php foreach ($option['option_value'] as $option_value) { ?>
                                            <option value="<?php echo $option_value['product_option_value_id']; ?>"><?php echo $option_value['name']; ?>
                                            <?php if ($option_value['price']) { ?>
                                                (<?php echo $option_value['price_prefix']; ?><?php echo $option_value['price']; ?>)
                                            <?php } ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            <?php } ?>
                            <?php if ($option['type'] == 'radio') { ?>
                                <div data-option-id="bundle_option-<?php echo $option['product_option_id']; ?>" class="option">
                                    <?php if ($option['required']) { ?>
                                        <span class="required">*</span>
                                    <?php } ?>
                                    <b><?php echo $option['name']; ?>:</b><br />
                                    <?php foreach ($option['option_value'] as $option_value) { ?>
                                        <div class="radio">
                                          <label for="option-value-<?php echo $option_value['product_option_value_id']; ?>">
                                            <input type="radio" name="option[<?php echo $i; ?>][<?php echo $option['product_option_id']; ?>]" id="option-value-<?php echo $option_value['product_option_value_id']; ?>" value="<?php echo $option_value['product_option_value_id']; ?>">
                                                <?php echo $option_value['name']; ?>
                                            <?php if ($option_value['price']) { ?>
                                                (<?php echo $option_value['price_prefix']; ?><?php echo $option_value['price']; ?>)
                                             <?php } ?>
                                          </label>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                            <?php if ($option['type'] == 'checkbox') { ?>
                                <div data-option-id="bundle_option-<?php echo $option['product_option_id']; ?>" class="option">
                                    <?php if ($option['required']) { ?>
                                        <span class="required">*</span>
                                    <?php } ?>
                                    <b><?php echo $option['name']; ?>:</b><br />
                                    <?php foreach ($option['option_value'] as $option_value) { ?>
                                        <div class="checkbox">
                                          <label for="option-value-<?php echo $option_value['product_option_value_id']; ?>">
                                            <input type="checkbox" name="option[<?php echo $i; ?>][<?php echo $option['product_option_id']; ?>][]" value="<?php echo $option_value['product_option_value_id']; ?>" id="option-value-<?php echo $option_value['product_option_value_id']; ?>" />
                                            <?php echo $option_value['name']; ?>
                                            <?php if ($option_value['price']) { ?>
                                                    (<?php echo $option_value['price_prefix']; ?><?php echo $option_value['price']; ?>)
                                            <?php } ?>
                                          </label>
                                        </div>
                                   <?php } ?>
                                </div>
                            <?php } ?>
                            <?php if ($option['type'] == 'image') { ?>
                                <div data-option-id="bundle_option-<?php echo $option['product_option_id']; ?>" class="option">
                                    <?php if ($option['required']) { ?>
                                        <span class="required">*</span>
                                    <?php } ?>
                                    <b><?php echo $option['name']; ?>:</b><br />
                                    <table class="option-image">
                                    <?php foreach ($option['option_value'] as $option_value) { ?>
                                        <tr>
                                            <td style="width: 1px;"><input type="radio" name="option[<?php echo $i; ?>][<?php echo $option['product_option_id']; ?>]" value="<?php echo $option_value['product_option_value_id']; ?>" id="option-value-<?php echo $option_value['product_option_value_id']; ?>" /></td>
                                            <td><label for="option-value-<?php echo $option_value['product_option_value_id']; ?>"><img src="<?php echo $option_value['image']; ?>" alt="<?php echo $option_value['name'] . ($option_value['price'] ? ' ' . $option_value['price_prefix'] . $option_value['price'] : ''); ?>" /></label></td>
                                            <td><label for="option-value-<?php echo $option_value['product_option_value_id']; ?>"><?php echo $option_value['name']; ?>
                                            <?php if ($option_value['price']) { ?>
                                                (<?php echo $option_value['price_prefix']; ?><?php echo $option_value['price']; ?>)
                                             <?php } ?>
                                            </label></td>
                                        </tr>
                                    <?php } ?>
                                    </table>
                                </div>
                            <?php } ?>
                            <?php if ($option['type'] == 'text') { ?>
                                <div data-option-id="bundle_option-<?php echo $option['product_option_id']; ?>" class="option">
                                    <?php if ($option['required']) { ?>
                                        <span class="required">*</span>
                                    <?php } ?>
                                    <b><?php echo $option['name']; ?>:</b><br />
                                    <input type="text" class="form-control" name="option[<?php echo $i; ?>][<?php echo $option['product_option_id']; ?>]" value="<?php echo $option['option_value']; ?>" />
                                </div>
                            <?php } ?>
                            <?php if ($option['type'] == 'textarea') { ?>
                                <div id="option-<?php echo $option['product_option_id']; ?>" class="option">
                                    <?php if ($option['required']) { ?>
                                        <span class="required">*</span>
                                    <?php } ?>
                                    <b><?php echo $option['name']; ?>:</b><br />
                                    <textarea  class="form-control" name="option[<?php echo $i; ?>][<?php echo $option['product_option_id']; ?>]" cols="16" rows="5"><?php echo $option['option_value']; ?></textarea>
                                </div>
                            <?php } ?>
                            <?php if ($option['type'] == 'file') { ?>
                                <div data-option-id="bundle_option-<?php echo $option['product_option_id']; ?>" class="option">
                                    <?php if ($option['required']) { ?>
                                        <span class="required">*</span>
                                    <?php } ?>
                                    <b><?php echo $option['name']; ?>:</b><br />
                                    
                                    <button type="button" id="button-upload<?php echo $option['product_option_id']; ?>" data-loading-text="Loading..." class="btn btn-default btn-block"><i class="fa fa-upload"></i> <?php echo $button_upload; ?></button>
                                    <input type="hidden" name="option[<?php echo $i; ?>][<?php echo $option['product_option_id']; ?>]" value="" />
                                </div>
                            <?php } ?>
                            <?php if ($option['type'] == 'date') { ?>
                                <div data-option-id="bundle_option-<?php echo $option['product_option_id']; ?>" class="option">
                                    <?php if ($option['required']) { ?>
                                        <span class="required">*</span>
                                    <?php } ?>
                                    <b><?php echo $option['name']; ?>:</b><br />
                                    <input type="text" data-date-format="YYYY-MM-DD" name="option[<?php echo $i; ?>][<?php echo $option['product_option_id']; ?>]" value="<?php echo $option['option_value']; ?>" class="PB_date form-control" />
                                </div>
                            <?php } ?>
                            <?php if ($option['type'] == 'datetime') { ?>
                                <div data-option-id="bundle_option-<?php echo $option['product_option_id']; ?>" class="option">
                                    <?php if ($option['required']) { ?>
                                        <span class="required">*</span>
                                    <?php } ?>
                                    <b><?php echo $option['name']; ?>:</b><br />
                                    <input type="text" name="option[<?php echo $i; ?>][<?php echo $option['product_option_id']; ?>]" value="<?php echo $option['option_value']; ?>" class="PB_datetime form-control" />
                                </div>
                            <?php } ?>
                            <?php if ($option['type'] == 'time') { ?>
                                <div data-option-id="bundle_option-<?php echo $option['product_option_id']; ?>" class="option">
                                    <?php if ($option['required']) { ?>
                                        <span class="required">*</span>
                                    <?php } ?>
                                    <b><?php echo $option['name']; ?>:</b><br />
                                    <input type="text" name="option[<?php echo $i; ?>][<?php echo $option['product_option_id']; ?>]" value="<?php echo $option['option_value']; ?>" class="PB_time form-control" />
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div> <!-- <div class="options"> -->
              <?php } ?>
                </div>
        <?php $i++; } ?>
        <input id="ProductBundlesProducts" type="hidden" name="products" value="<?php echo $BundleProducts; ?>" />
        <input id="ProductBundlesDiscount" type="hidden" name="discount" value="<?php echo $VoucherData; ?>" />
        <input id="ProductBundlesBundleID" type="hidden" name="bundle" value="<?php echo $BundleNumber; ?>" />
        <div style="clear:both"></div>
        </form><br />
        <div class="PB_colorbox_footer">
            <div class="PB_continue">
                <a id="ProductBundlesOptionsSubmitButton" class="btn btn-primary"><?php echo $Continue; ?></a>
            </div>
        </div>
        <script type="text/javascript">
        $('#ProductBundlesOptionsSubmitButton').on('click', function(e){
             $.ajax({
                url: 'index.php?route=<?php echo $modulePath; ?>/bundletocartoptions',
                type: 'post',
                data: $('#ProductBundlesOptionsForm').serialize(),
                dataType: 'json',
                success: function(json) {
                    $('.error').remove();
                    if (json['error']) {
                        if (json['error']['option']) {
                            for (i in json['error']['option']) {
                                for (n in json['error']['option'][i]) {
                                    $('div[data-product-index="' + json['error']['option'][i][n].key + '"]').find('div[data-option-id=bundle_option-' + i + ']').after('<span class="error">' + json['error']['option'][i][n].message + '</span>');
                                }
                            }
                        }
                    }
                    if (json['success']) {
                        parent.location = "<?php echo html_entity_decode($cart_url); ?>";	
                    }
                }
            });
        });
    
        $('.PB_date').datetimepicker({
            pickTime: false
        });
        
        $('.PB_datetime').datetimepicker({
            pickDate: true,
            pickTime: true
        });
        
        $('.PB_time').datetimepicker({
            pickDate: false
        });
    
        $('button[id^=\'button-upload\']').on('click', function() {
            var node = this;
            $('#form-upload').remove();
            $('body').prepend('<form enctype="multipart/form-data" id="form-upload" style="display: none;"><input type="file" name="file" /></form>');
            $('#form-upload input[name=\'file\']').trigger('click');
            $('#form-upload input[name=\'file\']').on('change', function() {
                $.ajax({
                    url: 'index.php?route=tool/upload',
                    type: 'post',
                    dataType: 'json',
                    data: new FormData($(this).parent()[0]),
                    cache: false,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $(node).button('loading');
                    },
                    complete: function() {
                        $(node).button('reset');
                    },
                    success: function(json) {
                        $('.text-danger').remove();
                        
                        if (json['error']) {
                            $(node).parent().find('input').after('<div class="text-danger">' + json['error'] + '</div>');
                        }
                        
                        if (json['success']) {
                            alert(json['success']);
                            
                            $(node).parent().find('input').attr('value', json['code']);
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    }
                });
            });
        });
        </script>
	<?php } ?>
<?php } ?>