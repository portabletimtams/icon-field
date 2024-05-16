(function ($) {
  function getObjectKey(obj, k, v) {
    for (const [key, value] of Object.entries(obj)) {
      if (value[k] == v) {
        return key
      }
    }
  }

  function setIconValue(val, input, selected, parent, config, source) {
    input.val(val)

    let vals = val.split(',')
    selected.html('')

    let ul = $('<ul>')

    let count = 0
    vals.forEach((i, li) => {
      let k = getObjectKey(source, 'value', i)

      if (k) {
        let item = source[k]
        ul.append('<li>' + item.admin_template + '</li>')
        parent.find('li[data-value="' + item.value + '"]').attr('data-selected', true)
        count++
      }
    })
    ul.attr('data-count', count)
    selected.html(ul)
  }

  function filterSelection(e, el) {
    clearTimeout(window.iconfilterSelectionTimeout)

    window.iconfilterSelectionTimeout = setTimeout(() => {
      let els = el.children('ul').children('li')
      let span = el.children('span')
      let searchStr = e.target.value

      if (searchStr == '') {
        el.attr('data-search', false)
        span.html('')
        els.each((i, li) => {
          li.setAttribute('data-display', true)
        })
      }
      else {
        el.attr('data-search', true)

        els.each((i, li) => {
          // let v = $(li);
          if (li.getAttribute('data-search-str').toLowerCase().search(searchStr.toLowerCase()) >= 0) {
            li.setAttribute('data-display', true)
          }
          else {
            li.setAttribute('data-display', false)
          }
        })

        let foundEls = el.children('ul').children('li[data-display="true"]')
        let count = parseInt(foundEls.length)
        span.html(count + ' icon' + (count > 1 ? 's' : '') + ' found')
      }
    }, 500)
  }

  function initSelections(source, el, input, selected, config) {
    let ul = $('<ul>')
    let span = $('<span>')
    let vals = []

    if (input && input.length) {
      vals = input[0].value.split(',')
    }

    for (const [key, value] of Object.entries(source)) {
      let v = value.value ? value.value : key

      let searchData = [v, value.title, value.source]

      ul.append(
        '<li data-value="'
        + v
        + '" data-search-str="'
        + searchData.join()
        + '" data-key="'
        + key
        + '" data-selected="'
        + (vals.includes(v.toString()) ? true : false)
        + '"><label>'
        + value.admin_template
        + '</label></li>',
      )
    }

    el.append(span)
    el.append(ul)

    el.find('li > label')
      .off('click')
      .on('click', (e) => {
        el.find('li').attr('data-selected', false) // diselect all

        setIconValue($(e.currentTarget).closest('li').attr('data-value'), input, selected, el, config, source)
      })
  }

  function removeIcon(e, input, selected) {
    let clickedIcon = e.currentTarget.getAttribute('data-value')
    let updatedVal = input
      .val()
      .replace(',' + clickedIcon, '')
      .replace(clickedIcon + ',', '')
      .replace(clickedIcon, '')
    input.val(updatedVal)

    let count = updatedVal.split(',')
    count = count > 0 ? count : 0

    selected.find('ul').attr('data-count', count)
    selected.find('ul').html('')
  }

  $(document).ready(() => {
    //
  })

  $('.cms-edit-form').entwine({
    onmatch(e) {
      this._super(e)
    },
    onunmatch(e) {
      this._super(e)
    },
    onaftersubmitform(event, data) {
      // ..
    },
  })

  $.entwine('ss', ($) => {
    $('[data-goldfinch-icon-field]').entwine({
      onmatch() {
        const config = JSON.parse($(this).attr('data-goldfinch-icon-config'))
        const source = JSON.parse($(this).attr('data-goldfinch-icon-source'))

        const input = $(this).find('[data-goldfinch-icon="key"]')
        const inputData = $(this).find('[data-goldfinch-icon="data"]')
        const selected = $(this).find('[data-goldfinch-icon-selected]')
        const loader = $(this).find('[data-goldfinch-icon-loader]')[0]
        const loaderBtn = $(this).find('[data-goldfinch-icon-loader] button')[0]
        const searchBox = $(this).find('[data-goldfinch-icon-search]')
        const selection = $(this).find('[data-goldfinch-icon-selection]')

        selected.find('li').on('click', (e) => {
          removeIcon(e, input, selected)
        })

        $(loaderBtn).on('click', () => {
          loader.remove()
          searchBox.removeClass('goldfinchicon__hide')
          searchBox.on('keydown', (e) => {
            filterSelection(e, selection)
          })
          initSelections(source, selection, input, selected, config)
          selection.removeClass('goldfinchicon__hide')
        })
        // ..
      },
    })
  })
})(jQuery)
