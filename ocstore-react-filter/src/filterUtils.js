/**
 * Utility functions for filter SEO URL parsing and generation
 */

/**
 * Parses the current SEO URL to extract filter parameters
 * @param {string} path - window.location.pathname
 * @param {Object} groups - available filter groups from API
 * @returns {Object} - parsed filters object
 */
export const parseSeoUrl = (path, groups) => {
  const parts = path.split('/').filter(Boolean);
  const filterIndex = parts.indexOf('filter');

  const parsed = { manufacturer: [], options: {}, attributes: {} };

  if (filterIndex === -1) {
    return parsed;
  }

  const segments = parts.slice(filterIndex + 1);

  segments.forEach(seg => {
    if (!seg.includes('=')) return;
    const [groupSlug, valueList] = seg.split('=');
    const valueSlugs = valueList.split(',');

    if (groupSlug === 'manufacturer') {
      const brandGroup = groups?.manufacturer?._0 || Object.values(groups?.manufacturer || {})[0];
      Object.values(brandGroup?.values || {}).forEach(input => {
        if (valueSlugs.includes(input.slug)) {
          parsed.manufacturer.push(parseInt(input.value));
        }
      });
    } else {
      let group = Object.values(groups?.attribute || {}).find(g => g.slug === groupSlug);
      let type = 'attribute';

      if (!group) {
        group = Object.values(groups?.option || {}).find(g => g.slug === groupSlug);
        type = 'option';
      }

      if (!group) return;

      Object.values(group.values || {}).forEach(item => {
        if (valueSlugs.includes(item.slug)) {
          const target = parsed[`${type}s`];
          target[group.group_id] = target[group.group_id] || [];
          target[group.group_id].push(parseInt(item.value));
        }
      });
    }
  });

  return parsed;
};

/**
 * Generates SEO URL based on active filters
 * @param {Object} filters - current active filters
 * @param {Object} groups - available filter groups from API
 * @returns {string} - generated SEO URL path
 */
export const generateSeoUrl = (filters, groups) => {
  const params = [];

  // Manufacturer
  if (filters.manufacturer?.length) {
    const brandGroup = groups?.manufacturer?._0 || Object.values(groups?.manufacturer || {})[0];
    const slugs = filters.manufacturer.map(id => {
      const brand = brandGroup?.values?.[`_${id}`] || Object.values(brandGroup?.values || {}).find(v => v.value == id);
      return brand?.slug;
    }).filter(Boolean);
    if (slugs.length) params.push(`manufacturer=${slugs.join(',')}`);
  }

  // Options & Attributes
  ['options', 'attributes'].forEach(type => {
    const groupType = type === 'attributes' ? 'attribute' : 'option';
    Object.entries(filters[type] || {}).forEach(([groupId, valueIds]) => {
      const group = groups?.[groupType]?.[`_${groupId}`] || groups?.[groupType]?.[groupId];
      if (!group) return;

      const slugs = valueIds.map(id => {
        const value = group.values?.[`_${id}`] || Object.values(group.values || {}).find(v => v.value == id);
        return value?.slug;
      }).filter(Boolean);

      if (slugs.length) {
        params.push(`${group.slug}=${slugs.join(',')}`);
      }
    });
  });

  const currentPath = window.location.pathname.split('/filter')[0];
  return params.length ? `${currentPath}/filter/${params.join('/')}` : currentPath;
};
