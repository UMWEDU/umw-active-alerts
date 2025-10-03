/******/ (() => { // webpackBootstrap
window.addEventListener('DOMContentLoaded', function () {
  var container = document.querySelector('article > .umw-block-content > .entry-content');
  if (!container) {
    return;
  }
  var alert = document.querySelector('.umw-page-alert');
  if (!alert) {
    return;
  }
  container.prepend(alert);
});
/******/ })()
;