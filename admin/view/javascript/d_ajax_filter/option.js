var d_ajax_filter = {
    //Настройки
    setting:{
        //текущая форма
        form: '',
        //Базовый url
        url:'index.php?route=extension/d_ajax_filter/option',
    },
    //Шаблоны
    template: {
        //шаблон колонки
        element: ''
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
            data:that.setting.form.find('input[name^=d_ajax_filter_options], select[name^=d_ajax_filter_options]').serializeJSON(),
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
    addOption:function(option_id, option_name){
        var data = {
            id:option_id,
            name:option_name,
            key:'options'
        };
        var content = this.templateСompile(this.template.new_element, data);
        if(this.setting.form.find('.table-option-select > tbody > tr#element-options-'+option_id).length == 0){
            this.setting.form.find('.table-option-select > tbody').append(content);
        }
    },
    //Компиляция шаблона
    templateСompile: function(template, data) {
        var source = template.html();
        var template = _.template(source);
        var html = template(data);
        return html;
    },
}