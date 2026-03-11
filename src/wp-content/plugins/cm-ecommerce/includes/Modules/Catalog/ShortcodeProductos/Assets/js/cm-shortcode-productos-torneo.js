(function () {
  'use strict';

  var config = window.cmProductosTorneo || {};
  var selectors = {
    root: '.js-cm-productos-torneo',
    grid: '.js-cm-productos-torneo-grid',
    button: '.js-cm-productos-torneo-load-more',
    feedback: '.js-cm-productos-torneo-feedback'
  };

  function asBool(value) {
    return value === '1' || value === 1 || value === true || value === 'true';
  }

  function setFeedback(node, message, isError) {
    if (!node) {
      return;
    }

    node.textContent = message || '';
    node.classList.toggle('is-error', !!isError);
  }

  function setButtonState(button, enabled) {
    if (!button) {
      return;
    }

    button.disabled = !enabled;
    button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
  }

  function updateFromPayload(grid, button, payload) {
    var hasMore = !!payload.has_more;
    var nextCursor = payload.next_cursor || {};
    var loaded = Number(grid.dataset.loadedCount || '0');

    if (typeof payload.loaded_count === 'number') {
      loaded += payload.loaded_count;
    }

    grid.dataset.loadedCount = String(loaded);
    grid.dataset.hasMore = hasMore ? '1' : '0';
    grid.dataset.nextCursorDate = nextCursor.date_gmt || '';
    grid.dataset.nextCursorId = nextCursor.id ? String(nextCursor.id) : '';

    if (payload.items_html) {
      grid.insertAdjacentHTML('beforeend', payload.items_html);
    }

    setButtonState(button, hasMore);
    if (!hasMore) {
      button.classList.add('is-hidden');
    }
  }

  function handleClick(root) {
    var grid = root.querySelector(selectors.grid);
    var button = root.querySelector(selectors.button);
    var feedback = root.querySelector(selectors.feedback);

    if (!grid || !button) {
      return;
    }

    if (!asBool(grid.dataset.hasMore)) {
      button.classList.add('is-hidden');
      return;
    }

    button.addEventListener('click', function () {
      var cursorDate = grid.dataset.nextCursorDate || '';
      var cursorId = Number(grid.dataset.nextCursorId || '0');

      if (!cursorDate || !cursorId) {
        setFeedback(feedback, (config.messages && config.messages.error) || 'Error', true);
        return;
      }

      setButtonState(button, false);
      button.classList.add('is-loading');
      setFeedback(feedback, (config.messages && config.messages.loading) || '', false);

      var formData = new FormData();
      formData.append('action', config.action || 'cm_productos_torneo_load_more');
      formData.append('nonce', config.nonce || '');
      formData.append('cursor_date_gmt', cursorDate);
      formData.append('cursor_id', String(cursorId));
      formData.append('already_loaded', String(Number(grid.dataset.loadedCount || '0')));

      fetch(config.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (json) {
          if (!json || json.success !== true || !json.data) {
            throw new Error('invalid_response');
          }

          updateFromPayload(grid, button, json.data);

          if (json.data.has_more) {
            setFeedback(feedback, '', false);
            return;
          }

          setFeedback(feedback, (config.messages && config.messages.end) || '', false);
        })
        .catch(function () {
          setFeedback(feedback, (config.messages && config.messages.error) || 'Error', true);
          setButtonState(button, true);
        })
        .finally(function () {
          button.classList.remove('is-loading');
        });
    });
  }

  document.querySelectorAll(selectors.root).forEach(handleClick);
})();
