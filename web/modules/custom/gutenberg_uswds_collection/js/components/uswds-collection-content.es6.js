/* eslint func-names: ["error", "never"] */
(function(wp, $, Drupal, DrupalGutenberg, drupalSettings) {
  const { element, components, blockEditor } = wp;
  const { Component, Fragment } = element;
  const { Placeholder, PanelBody, SelectControl, RangeControl } = components;
  const { InspectorControls, BlockIcon } = blockEditor;
  const __ = Drupal.t;

  async function getContent(item, viewMode = 'default') {
    const response = await fetch(
      Drupal.url(`editor/content/load/${item}/${viewMode}`),
      {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        }
      }
    );
    const content = await response.json();

    return content;
  }

  async function getLatestContent(items, viewMode = 'default') {
    const response = await fetch(
      Drupal.url(`editor/content/loadmultiple/${items}/${viewMode}`),
      {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        }
      }
    );
    const content = await response.json();

    return content;
  }

  async function getLatestNodes(bundle, items) {
    const response = await fetch(
      Drupal.url(`editor/latestcontent/load/${bundle}/${items}`),
      {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        }
      }
    );
    const content = await response.json();

    return content;
  }

  function processHtml(html) {
    const node = document.createElement('div');
    node.innerHTML = html;

    // Disable form elements.
    const formElements = node.querySelectorAll(
      'input, select, button, textarea, checkbox, radio',
    );
    formElements.forEach(element => {
      element.setAttribute('readonly', true);
      element.setAttribute('required', false);
      element.setAttribute('disabled', true);
    });

    // Disable links.
    const linkElements = node.querySelectorAll('a');
    linkElements.forEach(element => {
      element.setAttribute('href', 'javascript:void(0)');
    });

    return node.innerHTML;
  }

  function hasEmptyContent(html) {
    const node = document.createElement('div');
    node.innerHTML = html;

    return node.innerText.trim() ? false : true;
  }

  class UswdsCollectionContent extends Component {
    constructor() {
      super(...arguments);
      this.state = {
        htmlList: [],
        activeSuggestion: 0,
        filteredSuggestions: [],
        showSuggestions: false,
        selectedElement: null,
        userInput: ""
      };
      // Loading html if we have the node id.
      var { nodeIds, viewMode, useLatest } = this.props.attributes;
      if (nodeIds === null || nodeIds == undefined) {
        this.props.setAttributes({
          nodeIds: [],
        });
        nodeIds = [];
      }

      this.loadContent();

      // Binding this.
      this.onKeyDown = this.onKeyDown.bind(this);
      this.onChange = this.onChange.bind(this);
      this.onBlur = this.onBlur.bind(this);
      this.onClick = this.onClick.bind(this);
      this.clearContent = this.clearContent.bind(this);
      this.renderNodes = this.renderNodes.bind(this);
      this.renderLatestNodes = this.renderLatestNodes.bind(this);
      this.loadContent = this.loadContent.bind(this);
      this.selectItemForRemoval = this.selectItemForRemoval.bind(this);
      this.removeItem = this.removeItem.bind(this);
      this.moveItemUp = this.moveItemUp.bind(this);
      this.moveItemDown = this.moveItemDown.bind(this);
      this.addItem = this.addItem.bind(this);
    }

    renderLatestNodes(range){
      getLatestNodes(this.props.contentType, range)
      .then(nids => {
        this.props.setAttributes({
          nodeIds: nids,
        });
        this.renderNodes(nids);
      });
    }

    loadContent(range = this.props.attributes.collectionRange, nodeIds = this.props.attributes.nodeIds, viewMode = this.props.attributes.viewMode, useLatest = this.props.attributes.useLatest){
      if ( (nodeIds != null && nodeIds != undefined) || useLatest == true ){
        if (useLatest == true) {
          this.renderLatestNodes(range);
        }
        else {
          this.renderNodes(nodeIds, viewMode);
        }
      }
    }

    renderNodes(nodeIds = this.props.attributes.nodeIds, viewMode = this.props.attributes.viewMode){
      var _SELF = this;
      getLatestContent(nodeIds.join(","), viewMode)
      .then(content => {
        _SELF.setState({
          htmlList: content
        });
      });
    }

    componentDidMount() {
      const { nodeIds } = this.props.attributes;
      if (nodeIds) {
        // Dispatching event.
        const event = new CustomEvent('contentEmbedded', { detail: { contentType: this.props.contentType } });
        document.dispatchEvent(event);
      }
    }

    onChange(event) {
      const { contentType } = this.props;
      const userInput = event.currentTarget.value;

      this.setState({ userInput });

      if (userInput) {
        fetch('/editor/search-collection/' + contentType + '/' + userInput, {
          method: 'GET',
          headers: {
            'Accept': 'application/json'
          }
        }).then((response) => {
          if (response.ok) {
            response.json().then(data => {
              this.setState({
                activeSuggestion: 0,
                filteredSuggestions: data,
                showSuggestions: true,
                userInput: userInput
              });
            })
          }
          else {
            // Show in console on error.
            console.log(response);
          }
        });
      }
      else {
        this.setState({
          activeSuggestion: 0,
          filteredSuggestions: [],
          showSuggestions: false,
          userInput: userInput
        });
      }
    }

    onClick(event) {
      const nid = event.currentTarget.getAttribute('nid');
      const { nodeIds, viewMode } = this.props.attributes;
      if (nid) {
        const nodeLabel = event.currentTarget.innerText;
        var newArray = nodeIds;
        newArray.push(nid);
        this.props.setAttributes({
          nodeIds: newArray,
        });

        getContent(nid, viewMode)
        .then(content => {
          var newHtml = this.state.htmlList;
          newHtml.push(content);
          this.setState({
            userInput: "",
            htmlList: newHtml,
          });
          // Dispatching event.
          const event = new CustomEvent('contentEmbedded', { detail: { contentType: this.props.contentType } });
          document.dispatchEvent(event);
        })
        .catch(r => {
          var newHtml = this.state.htmlList;
          newHtml.push(__('An error occured when loading the content.') + r);
          this.setState({
            htmlList: newHtml,
            userInput: nodeLabel,
          });
        });
      }
    }

    onKeyDown(event) {
      const { activeSuggestion, filteredSuggestions } = this.state;
      if (event.keyCode === 13) {
        this.setState({
          activeSuggestion: 0,
          showSuggestions: false,
          userInput: filteredSuggestions[activeSuggestion]
        });
      } else if (event.keyCode === 38) {
        if (activeSuggestion === 0) {
          return;
        }
        this.setState({ activeSuggestion: activeSuggestion - 1 });
      }
      // User pressed the down arrow, increment the index
      else if (event.keyCode === 40) {
        if (activeSuggestion - 1 === filteredSuggestions.length) {
          return;
        }
        this.setState({ activeSuggestion: activeSuggestion + 1 });
      }
    }

    onBlur(event) {
      const { activeSuggestion, filteredSuggestions } = this.state;
      setTimeout(() => {
        this.setState({
          showSuggestions: false,
          activeSuggestion: 0,
          userInput: "",
        });
      }, 500);
    }

    clearContent(event) {
      this.props.setAttributes({
        nodeIds: [],
      });
      this.setState({
        htmlList: [],
      });
    }

    removeItem() {
      if (this.state.selectedElement != null){
        let id = this.state.selectedElement;
        var newList = this.state.htmlList.filter((_, index) => index != id);
        this.setState({
          htmlList: newList,
          selectedElement: null
        });
        var updatedIds = this.props.attributes.nodeIds.filter((_, index) => index != id);
        this.props.setAttributes({
          nodeIds: updatedIds,
        });
      }
    }

    selectItemForRemoval(id) {
      this.setState({ selectedElement: id });
    }

    addItem() {
      this.props.setAttributes({
        collectionRange: this.props.attributes.collectionRange+1
      });
    }

    moveItemUp() {
      let index = this.state.selectedElement;
      index = +index;
      if (index > 0 && this.state.selectedElement != null) {
        let newHtmlList = this.state.htmlList;
        let [element] = newHtmlList.splice(index, 1);
        newHtmlList.splice(index - 1, 0, element);
        this.setState({
          htmlList: newHtmlList,
          selectedElement: index - 1
        });

        let newNodeslist = this.props.attributes.nodeIds;
        let [ele] = newNodeslist.splice(index, 1);
        newNodeslist.splice(index - 1, 0, ele);
        this.props.setAttributes({
          nodeIds: newNodeslist,
        });
      }
    }

    moveItemDown() {

      let index = this.state.selectedElement;
      index = +index;
      let newHtmlList = this.state.htmlList;
      let newNodeslist = this.props.attributes.nodeIds;
      if (index < newNodeslist.length - 1 && this.state.selectedElement != null && newHtmlList) {
        let [element] = newHtmlList.splice(index, 1);
        newHtmlList.splice(index + 1, 0, element);
        this.setState({
          htmlList: newHtmlList,
          selectedElement: index + 1
        });

        let [ele] = newNodeslist.splice(index, 1);
        newNodeslist.splice(index + 1, 0, ele);
        this.props.setAttributes({
          nodeIds: newNodeslist,
        });
      }
    }

    render() {
      const {
        onChange,
        onBlur,
        onClick,
        onKeyDown,
        clearContent,
        selectItemForRemoval,
        removeItem,
        addItem,
        moveItemUp,
        moveItemDown,
        state: {
          htmlList,
          activeSuggestion,
          filteredSuggestions,
          showSuggestions,
          userInput,
          selectedElement
        }
      } = this;
      const { className, viewModes, attributes, setAttributes, contentTypeLabel, isSelected } = this.props;
      if (attributes.nodeIds === undefined) {
        attributes.nodeIds = [];
      }
      // Suggestions List.
      let suggestionsListComponent;
      if (showSuggestions && userInput) {
        if (filteredSuggestions.length) {
          suggestionsListComponent = (
            <ul class="suggestions">
              {filteredSuggestions.map((suggestion, index) => {
                let className;

                // Flag the active suggestion with a class
                if (index === activeSuggestion) {
                  className = "suggestion-active";
                }
                return (
                  <li className={className} key={suggestion.id} nid={suggestion.id} onClick={onClick}>
                    {suggestion.title}
                  </li>
                );
              })}
            </ul>
          );
        } else {
          suggestionsListComponent = (
            <div class="no-suggestions">
              <em>No suggestions available.</em>
            </div>
          );
        }
      }
      // View modes options.
      let viewModesOptions = []
      for (const viewModeId in viewModes) {
        if (Object.hasOwnProperty.call(viewModes, viewModeId)) {
          const viewModeLabel = viewModes[viewModeId];
          viewModesOptions.push({ value: viewModeId, label: viewModeLabel });
        }
      }

      let collectionTypeClass = "usa-collection ";
      collectionTypeClass += "view-mode--" + attributes.viewMode;

      // Render.
      return (
        <Fragment>
          <Fragment>
            <InspectorControls>
              <Fragment>
                <PanelBody title={__('Collection Settings')} initialOpen={true}>
                  <SelectControl
                    label={__('View mode')}
                    value={attributes.viewMode}
                    options={viewModesOptions}
                    onChange={newViewMode => {
                      setAttributes({
                        viewMode: newViewMode
                      });
                      if (attributes.nodeIds.length > 0) {
                        this.loadContent(attributes.collectionRange, attributes.nodeIds, newViewMode);
                      }
                      // Dispatching event.
                      const event = new CustomEvent('changedViewMode', { detail: { contentType: this.props.contentType, viewMode: newViewMode } });
                      document.dispatchEvent(event);
                    }}
                  />
                  <RangeControl
                    value={ attributes.collectionRange }
                    onChange={width => {
                      setAttributes({
                        collectionRange: width
                      });
                      this.loadContent(width);
                    }}
                    min={ 1 }
                    max={ 20 }
                    step={ 1 }
                  />
                  <ToggleControl
                    label={ __('Use latest nodes') }
                    checked={ attributes.useLatest }
                    onChange={newUse => {
                      setAttributes({
                        useLatest: !attributes.useLatest
                      });
                      this.loadContent(attributes.collectionRange, attributes.nodeIds, attributes.viewMode, !attributes.useLatest);
                    }}
                  />
                </PanelBody>
              </Fragment>
            </InspectorControls>
            <BlockControls>
              {selectedElement != null ?
                <Toolbar
                  controls={
                  [
                    {
                      icon: 'redo',
                      title: __('Clear content'),
                      onClick: clearContent,
                    },
                    {
                      icon: 'no',
                      title: __('Remove item'),
                      onClick: removeItem,
                    },
                    {
                      icon: 'arrow-up-alt',
                      title: __('Move Up'),
                      onClick: moveItemUp,
                    },
                    {
                      icon: 'arrow-down-alt',
                      title: __('Move down'),
                      onClick: moveItemDown,
                    },
                  ]
                }
                >
                </Toolbar>
                :
                <Toolbar
                  controls={[
                    {
                      icon: 'redo',
                      title: __('Clear content'),
                      onClick: clearContent,
                    },
                  ]}
                >
                </Toolbar>
              }
            </BlockControls>
          </Fragment>
          { attributes.viewMode == 'featured' || attributes.viewMode == 'section_landing' ?
            <div class={collectionTypeClass}>
              {Object.entries(htmlList).map(([key, item]) => (
                <div
                  key={key}
                  dangerouslySetInnerHTML={{ __html: processHtml(item) }}
                  onClick={() => this.selectItemForRemoval(key)}
                  className={selectedElement == key && isSelected ? 'selected__element' : 'unselected__element'}
                />
              ))}
            </div>
          :
            <ul class={collectionTypeClass}>
              {Object.entries(htmlList).map(([key, item]) => (
                <div
                  key={key}
                  dangerouslySetInnerHTML={{ __html: processHtml(item) }}
                  onClick={() => this.selectItemForRemoval(key)}
                  className={selectedElement == key && isSelected ? 'selected__element' : 'unselected__element'}
                />
              ))}
            </ul>
          }

          { attributes.nodeIds.length < attributes.collectionRange && isSelected &&
            <Fragment>
              <Placeholder
                icon={<BlockIcon icon="media-document" />}
                label={__('Content :') + contentTypeLabel}
                instructions={__('Start typing to find the content you want to embed.')}
                className="content-embed-autocomplete"
              >
                <input
                  type="text"
                  onChange={onChange}
                  onKeyDown={onKeyDown}
                  onBlur={onBlur}
                  placeholder={__('Search for content...')}
                />
                {suggestionsListComponent}
              </Placeholder>
            </Fragment>
          }
          { isSelected && attributes.nodeIds.length == attributes.collectionRange && attributes.nodeIds.length < 20 &&
            <Button onClick={addItem} className="button button-large">
              {__('Add collection item')}
            </Button>
          }
        </Fragment>
      );
    }
  }

  window.DrupalGutenberg = window.DrupalGutenberg || {};
  window.DrupalGutenberg.Components = window.DrupalGutenberg.Components || {};
  window.DrupalGutenberg.Components.UswdsCollectionContent = UswdsCollectionContent;
})(wp, jQuery, Drupal, DrupalGutenberg, drupalSettings);
