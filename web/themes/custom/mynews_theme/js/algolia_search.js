const searchClient = algoliasearch('EPRXYU1JRC', '6539f2f10d9b0929b06dfa5ce77335b7');

const search = instantsearch({
  indexName: 'news_articles',
  searchClient,
});

search.addWidgets([
  instantsearch.widgets.searchBox({
    container: '#searchbox',
    placeholder: 'Search news articles...',
  }),

  instantsearch.widgets.hits({
    container: '#hits',
    templates: {
      item(hit) {
        return `
          <div class="hit">
            <h2>${hit.title}</h2>
            <p>${hit.body ? hit.body.substring(0, 200) + '...' : ''}</p>
            ${hit.image_url ? `<img src="${hit.image_url}" alt="${hit.title}" style="max-width: 200px;" />` : ''}
          </div>
        `;
      },
    },
  }),
]);

search.start();
