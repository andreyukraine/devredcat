<d_ajax_filter_selected class={empty && 'empty-wrapper'} if={empty}>
    <div class="sel-filtr">
        <div class="title-selected-filter">Вибрані фільтри:</div>
        <div class="selected-list clearfix">
            <!-- Ітеруємо кожну групу та назву в обраних фільтрах -->
            <div each={groups, name in store.getState().selected}>
                <div class="item-selected" each={group, group_id in groups} if={Object.keys(group).length > 0}>
                    <!-- Відображення назви групи фільтрів -->
                    <div class="filter-group-name">
                        {getGroupCaption2(name, group_id)}
                    </div>

                    <!-- Відображення кожного елементу в групі -->
                    <af_selected each={value in group} if={!store.checkRange(name, group_id)}>
                        <span class="selected-option-name">
                            {store.getElementCaption(name, group_id, value) || 'Назва опції не знайдена'}
                        </span>
                    </af_selected>

                    <!-- Відображення діапазону значень для груп з range фільтрами -->
                    <af_selected_range group={group} name={name} group_id={group_id} if={store.checkRange(name, group_id)}>
                    </af_selected_range>
                </div>
            </div>
        </div>
    </div>
    <!-- Кнопка для скидання фільтрів -->
    <div class="button-reset" id="resetFilter" onclick={click}>
        <span></span><p>{store.getState().translate.button_reset}</p>
    </div>
</div>

<script>
    this.mixin({ store: d_ajax_filter });

    // Функція для отримання назви групи через caption з filter.group_id
    this.getGroupCaption2 = function(name, group_id) {
        const groups = this.store.getState().groups;
        const groupKey = `_${group_id}`;
        if (groups[name] && groups[name][groupKey] && groups[name][groupKey].caption) {
            return groups[name][groupKey].caption;
        }
        return `${name} (${group_id})`;
    };

    // Ініціалізація прапорця `empty`, що показує, чи є вибрані фільтри
    this.empty = _.isEmpty(this.store.getState().selected) || _.every(this.store.getState().selected, (value) => _.isEmpty(value[0]));

    // Очищення всіх обраних фільтрів
    click(e) {
        this.store.clearSelectedAll(this.opts.id);
    }

    // Оновлення прапорця `empty` при зміні стану
    this.on('update', function() {
        this.empty = _.isEmpty(this.store.getState().selected) || _.every(this.store.getState().selected, (value) => _.isEmpty(value[0]));
    });
</script>
</d_ajax_filter_selected>
