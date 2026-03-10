import React from 'react';
import ReactDOM from 'react-dom';

const Portal = ({ containerId, children }) => {
  const container = document.getElementById(containerId);
  return container ? ReactDOM.createPortal(children, container) : null;
};

export default Portal;
