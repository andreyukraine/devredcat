var d_ajax_filter = {
    //Настройки
    setting:{
        //текущая форма
        form: '',
        //Базовый url
        url:'index.php?route=extension/d_ajax_filter/attribute',
        //token
        token:''
    },
    //Шаблоны
    template: {
        //шаблон колонки
        element: '',
        //Шаблон тегов option для Select
        options:'',
        //Шаблон значений аттрибутов
        attribute_values:'',
        //Шаблон изображений аттрибутов
        attribute_images:'',
        //Шаблон менеджера изображений
        filemanager:''
    },
    //Инициализация
    init: function(setting){
        this.setting = $.extend({}, this.setting, setting);
        this.initPartial();
    },
    //Инициализация шаблонов
    initTemplate: function(template) {
        this.template = $.extend({}, this.template, template);
    },
    //Инициализация Handlebars Partial
    initPartial: function() {
        if (window.Handlebars !== undefined) {
            console.log('d_visual_designer:init_partials');
            window.Handlebars.registerHelper('select', function(value, options) {
                var $el = $('<select />').html(options.fn(this));
                $el.find('[value="' + value + '"]').attr({ 'selected': 'selected' });
                return $el.html();
            });
            window.Handlebars.registerHelper('concat', function(value, options) {
                var res = [];
                for (var key in value) {
                    res.push(value[key]['setting']['size']);
                }
                return res.join(options['hash']['chart']);
            });
            window.Handlebars.registerHelper('ifCond', function(v1, v2, options) {
                if (v1 === v2) {
                    return options.fn(this);
                }
                return options.inverse(this);
            });
        }

    },
    //Создание Sortable
    createSortable:function(selector,child){

        tinysort(selector+' > '+child, {selector:'input.sort-value',useVal:true});

        var that = this;
        Sortable.create($(selector)[0], {
            animation: 100,
            sort: true,
            onUpdate: function (ev){
                that.setting.form.find(selector+' > '+child).each(function (i, row) {
                    $(row).find('.sort-value').val(i)
                });
                that.setting.form.find('#attribute > #saveValues').show();
            }
        });
    },
    //Обновление Sort Order
    updateSortOrder:function(selector, child){
        this.setting.form.find(selector+' > '+child).each(function (i, row) {
            $(row).find('.sort-value').val(i)
        });
    },
    //Сохранение настроек аттрибутов
    save:function(){
        var that = this;
        $.ajax({
            url:that.setting.form.attr('action'),
            type:'post',
            dataType:'json',
            data:that.setting.form.find("#d_list_attribute").find('input[name^=d_ajax_filter_attributes], select[name^=d_ajax_filter_attributes]').serializeJSON(),
            beforeSend:function(){
                that.setting.form.fadeTo('slow', 0.5);
            },
            success:function(json){
                that.setting.form.find('.form-group.has-error').removeClass('has-error');
                that.setting.form.find('.form-group .text-danger').remove();
                $('.alert').remove();
                if(json['success']){
                    location.href = json['redirect'];
                }
                if(json['error']){
                    for (var key in json['errors']){
                        that.setting.form.find('[data-error="'+key+'"]').after('<div class="text-danger">'+json['errors'][key]+'</div>');
                        that.setting.form.find('[data-error="'+key+'"]').closest('.form-group').addClass('has-error');
                    }
                    $('#content > .container-fluid').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+json['error']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
                }
            },
            complete:function(){
                that.setting.form.fadeTo('slow', 1);
            }
        });
    },
    addAttribute:function(attribute_id, attribute_name){
        var data = {
            id:attribute_id,
            name:attribute_name,
            key:'attributes'
        };
        var content = this.templateСompile(this.template.new_element, data);
        if(this.setting.form.find('.table-attribute-select > tbody > tr#element-attributes-'+attribute_id).length == 0){
            this.setting.form.find('.table-attribute-select > tbody').append(content);
        }
    },
    //Сброс сортировки значений атрибутов
    resetSortOrder:function(selector, child){
        tinysort(selector+' > '+child, 'span.text', {natural:true});
        this.updateSortOrder(selector, child);
        this.setting.form.find('#attribute > #saveValues').show();
    },
    //Сброс изображений значений атрибутов
    resetImageAttribute:function(){
        $('#attribute_images .img-thumbnail').each(function(){
            $(this).attr('src', $(this).data('placeholder'));
            $(this).prev().val('');
        });
    },
    //Сохранения порядка аттрибутов
    saveAttributeValues:function(){
        var that = this;
        $.ajax({
            type:'post',
            url:that.setting.url+'/editAttributeValues&'+that.setting.token,
            data: $("div#attribute_values input").serializeJSON(),
            dataType: 'json',
            success:function(){
                that.setting.form.find('a#saveValues').hide();
            }
        });
    },

    //Сохранения порядка аттрибутов
    saveAttributeImages:function(){
        var that = this;
        $.ajax({
            type:'post',
            url:that.setting.url+'/editAttributeImages&'+that.setting.token,
            data: $("div#attribute_images input").serializeJSON(),
            dataType: 'json',
            beforeSend:function(){
                that.setting.form.find('a#saveImages').button('loading');
            },
            complete:function(){
                that.setting.form.find('a#saveImages').button('reset');
            }
        });
    },
    //Компиляция шаблона
    templateСompile: function(template, data) {
        var source = template.html();
        var template = _.template(source);
        var html = template(data);
        return html;
    },
    //Отрисовка аттрибутов
    renderAttributeGroups:function(language_id, target){

        var that = this;
        $.ajax({
            type:'post',
            url:that.setting.url+'/getAttributeGroups&'+that.setting.token,
            data: {language_id:language_id},
            dataType: 'json',
            success:function(json){
                var content = that.templateСompile(that.template.options,{'values':json['values']});
                that.setting.form.find(target).find('option[value!="*"]').remove();
                that.setting.form.find(target).append(content);
            }
        });
    },
    //Отрисовка аттрибутов
    renderAttributes:function(attribute_group_id, language_id, target){
        var data = {
            attribute_group_id:attribute_group_id,
            language_id:language_id
        };

        var that = this;
        $.ajax({
            type:'post',
            url:that.setting.url+'/getAttributes&'+that.setting.token,
            data: data,
            dataType: 'json',
            success:function(json){
                var content = that.templateСompile(that.template.options,{'values':json['values']});
                that.setting.form.find(target).find('option[value!="*"]').remove();
                that.setting.form.find(target).append(content);
            }
        });
    },

    //отрисовка значений атрибута 
    renderAttributeValues:function(attribute_id, language_id){

        this.setting.form.find('#attribute > #saveValues').hide();

        var data = {
            attribute_id: attribute_id,
            language_id:language_id
        };

        var that = this;

        $.ajax({
            type:'post',
            url:that.setting.url+'/getAttributeValues&'+that.setting.token,
            data: data,
            dataType: 'json',
            success:function(json){
                var content = that.templateСompile(that.template.attribute_values,{'values':json['values']});
                that.setting.form.find("div#attribute_values").html(content);
                that.createSortable('#attribute_values .sortable', 'div',that.updateAttributeValues);

                if(Object.keys(json['values']).length > 0){
                    that.setting.form.find('#attribute > #reset_sort_order').show();
                }
                else{
                    that.setting.form.find('#attribute > #reset_sort_order').hide();
                }
            }
        });
    },

    

    //Отрисовка изображений аттрибутов
    renderAttributeImages:function(attribute_id, language_id){
        var data = {
            attribute_id:attribute_id,
            language_id:language_id
        };

        var that = this;
        $.ajax({
          type:'post',
          url:that.setting.url+'/getAttributeImages&'+that.setting.token,
          data: data,
          dataType: 'json',
          success:function(json){
            if(json['success']){
                var content = that.templateСompile(that.template.attribute_images,{'values':json['values']});

                that.setting.form.find("div#attribute_images").html(content);
                that.updateFileManager();

                if(Object.keys(json['values']).length > 0){
                    that.setting.form.find('#attribute_image > #saveImages').show();
                    that.setting.form.find('#attribute_image > #reset_image_attribute').show();
                }
                else{
                    that.setting.form.find('#attribute_image > #saveImages').hide();
                    that.setting.form.find('#attribute_image > #reset_image_attribute').hide();
                }
            }
        }
    });
    },
    //Очистка изображений атррибутов
    clearAttributeImages:function(){
        this.setting.form.find('div#attribute_images').html('');
        this.setting.form.find('#attribute_image > #saveImages').hide();
        this.setting.form.find('#attribute_image > #reset_image_attribute').hide();
    },
    //Очистка значений атррибутов
    clearAttributeValues:function(){
        this.setting.form.find('div#attribute_values').html('');
        this.setting.form.find('#attribute > #reset_sort_order').hide();
    },

    updateFileManager:function() {
        var that = this;
        this.setting.form.find('.img-thumbnail').on('click', function (e) {
            that.uploadImage($(this).prev().attr("id"), $(this).attr("id"));
            e.stopPropagation();
        }); 
        
        this.setting.form.find('.delete-image').on('click', function(e){
            $(this).prev().prev().val("");
            $(this).prev().attr("src", that.setting.placeholder);
            e.stopPropagation();
        });
    },
    uploadImage:function(field, thumb) {
        $('#modal-image').remove();
        var content = this.templateСompile(this.template.filemanager,{field:field, thumb:thumb});

        $('body').append(content);

        $('#modal-image').modal('show');
        $('.modal-backdrop').remove();
    }
}