import React from 'react';

const ActiveFilters = ({ filters, groups, onRemove, onResetAll }) => {
  const renderFilterChips = () => {
    const chips = [];

    // Бренди
    if (filters.manufacturer?.length > 0) {
      const manufacturerGroup = groups.manufacturer?._0 || Object.values(groups.manufacturer || {})[0];
      chips.push({
        groupLabel: manufacturerGroup?.caption || 'Бренди',
        items: filters.manufacturer.map(id => {
          const brand = manufacturerGroup?.values?.[`_${id}`] || Object.values(manufacturerGroup?.values || {}).find(v => v.value == id);
          return {
            id: `manufacturer-${id}`,
            label: brand?.name || `Бренд ${id}`,
            onClick: () => {
              onRemove('manufacturer', id);
            }
          };
        })
      });
    }

    // Опції
    if (filters.options) {
      Object.entries(filters.options).forEach(([groupId, valueIds]) => {
        if (!valueIds?.length) return;

        const group = groups.option?.[`_${groupId}`] || groups.option?.[groupId];
        chips.push({
          groupLabel: group?.caption || `Опції ${groupId}`,
          items: valueIds.map(valueId => {
            const value = group?.values?.[`_${valueId}`] || Object.values(group?.values || {}).find(v => v.value == valueId);
            return {
              id: `option-${groupId}-${valueId}`,
              label: value?.name || `Опція ${valueId}`,
              onClick: () => {
                onRemove('option', { groupId, valueId });
              }
            };
          })
        });
      });
    }

    // Атрибути
    if (filters.attributes) {
      Object.entries(filters.attributes).forEach(([groupId, valueIds]) => {
        if (!valueIds?.length) return;

        const group = groups.attribute?.[`_${groupId}`] || groups.attribute?.[groupId];
        chips.push({
          groupLabel: group?.caption || `Атрибути ${groupId}`,
          items: valueIds.map(valueId => {
            const value = group?.values?.[`_${valueId}`] || Object.values(group?.values || {}).find(v => v.value == valueId);
            return {
              id: `attribute-${groupId}-${valueId}`,
              label: value?.name || `Атрибут ${valueId}`,
              onClick: () => {
                onRemove('attribute', { groupId, valueId });
              }
            };
          })
        });
      });
    }

    return chips;
  };

  const filterChips = renderFilterChips();

  if (!filterChips.length) return null;

  return (
    <div className="active-filters-wrapper">
      <div className="active-filters">
        {filterChips.map((group, index) => (
          <div key={`group-${index}`} className="filter-group">
            <span className="filter-group-label">{group.groupLabel}</span>
            <div className="filter-chips">
              {group.items.map(chip => (
                <div
                  key={chip.id}
                  className="filter-chip"
                  onClick={() => {
                    chip.onClick();
                  }}>
                  {chip.label} <span className="chip-close">×</span>
                </div>
              ))}
            </div>
          </div>
        ))}
        <button className="reset-filters-btn" onClick={onResetAll}>Скинути фільтри</button>
      </div>

    </div>
  );
};

export default ActiveFilters;
