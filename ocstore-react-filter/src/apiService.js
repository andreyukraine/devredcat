// apiService.js
export const getFilteredProducts = async (params) => {
  const response = await fetch(`index.php?route=extension/module/d_ajax_filter/ajax&${new URLSearchParams(params)}`);
  return await response.json();
};
