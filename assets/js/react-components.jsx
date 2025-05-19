(function () {
  'use strict';

  class SplitSelector extends React.Component {
    static propTypes = {
      linkedIds: PropTypes.arrayOf(PropTypes.number),
      endpoint: PropTypes.string,
      formId: PropTypes.string,
      valueKey: PropTypes.string,
      displayKey: PropTypes.string,
      findGroup: PropTypes.func,
      onSuccess: PropTypes.func,
    };

    constructor(props) {
      super(props);

      this.state = {
        linkedIds: new Set(this.props.linkedIds),
        query: '',
      };

      this.linkedSelect = React.createRef();
      this.unlinkedSelect = React.createRef();
      this.searchInput = React.createRef();

      this.handleLink = this.handleLink.bind(this);
      this.handleUnlink = this.handleUnlink.bind(this);
      this.handleSubmit = this.handleSubmit.bind(this);
      this.handleSearch = this.handleSearch.bind(this);
      this.handleClearSearch = this.handleClearSearch.bind(this);
    }

    handleLink(e) {
      e.preventDefault();

      this.handleSelection(this.unlinkedSelect, 'add');
    }

    handleUnlink(e) {
      e.preventDefault();

      this.handleSelection(this.linkedSelect, 'delete');
    }

    handleSelection(ref, setMethod) {
      const newState = { linkedIds: new Set(this.state.linkedIds) };
      const $select = $(ref.current);
      const $selectedOptions = $select.find(':selected');
      $selectedOptions.prop('selected', false).each((_, el) => {
        newState.linkedIds[setMethod](Number(el.value));
      });
      this.setState(newState);
    }

    handleSubmit(e) {
      e.preventDefault();
      const ids = Array.from(this.state.linkedIds).join(',');

      $.Dialog.wait(false, 'Saving changes');

      $.API.put(this.props.endpoint, { ids }, data => {
        if (!data.status) return $.Dialog.fail(false, data.message);

        this.props.onSuccess(data);
      });
    }

    handleSearch(e) {
      this.setQuery(e.target.value.trim());
    }

    handleClearSearch(e) {
      e.preventDefault();

      this.searchInput.current.value = '';
      this.setQuery('');
    }

    setQuery(query) {
      this.setState({ ...this.state, query });
    }

    render() {
      const { linkedIds, query } = this.state;
      const { formId, groups, valueKey, displayKey, entries } = this.props;

      const entriesByGroup = Object.keys(groups).reduce((a, c) => ({ ...a, [c]: [] }), {});
      entries.forEach(entry => {
        const group = this.props.findGroup(entry);
        entriesByGroup[group].push(entry);
      });

      const linkedGroups = [];
      const unlinkedGroups = [];
      const searching = query !== '';

      const elToOption = el => <option key={el[valueKey]} value={el[valueKey]}>{el[displayKey]}</option>;
      const placeholderOptionIfEmpty = array => array.length > 0 ? array.map(elToOption) :
        <option disabled>(none)</option>;

      $.each(groups, (group, label) => {
        const linkedEntries = [];
        const unlinkedEntries = [];
        entriesByGroup[group].forEach(entry => {
          if (searching && entry[displayKey].toLowerCase().indexOf(query.toLowerCase()) === -1)
            return;
          if (linkedIds.has(entry.id))
            linkedEntries.push(entry);
          else unlinkedEntries.push(entry);
        });
        linkedGroups.push(<optgroup key={group} label={label}>
          {placeholderOptionIfEmpty(linkedEntries)}
        </optgroup>);
        unlinkedGroups.push(<optgroup key={group} label={label}>
          {placeholderOptionIfEmpty(unlinkedEntries)}
        </optgroup>);
      });

      return (<form id={formId} onSubmit={this.handleSubmit}>
        <div className="split-select-wrap">
          <div className="filter-input">
            <input
              ref={this.searchInput}
              type="text"
              placeholder="Search"
              onChange={this.handleSearch}
              spellCheck={false} />
            <button className="typcn typcn-times red"
                    onClick={this.handleClearSearch}
                    disabled={query === ''}>
              Clear
            </button>
          </div>
          <div className="split-select">
            <span>Linked</span>
            <select ref={this.linkedSelect} name="listed" multiple={true}>
              {linkedGroups}
            </select>
          </div>
          <div className="buttons">
            <button className="typcn typcn-chevron-left green" title="Link selected" onClick={this.handleLink} />
            <button className="typcn typcn-chevron-right red" title="Unlink selected" onClick={this.handleUnlink} />
          </div>
          <div className="split-select">
            <span>Available</span>
            <select ref={this.unlinkedSelect} multiple={true}>
              {unlinkedGroups}
            </select>
          </div>
        </div>
      </form>);
    }
  }

  const WSS = ({ responseTimeHistorySize }) => {
    const [responseTimes, setResponseTimes] = React.useState([]);
    const [statusClass, setStatusClass] = React.useState('info');
    const [statusString, setStatusString] = React.useState('Checking status…');
    const [networks, setNetworks] = React.useState([]);
    const connsRef = React.useRef({});
    const [heartBeat, setHeartBeat] = React.useState(false);
    const [heartDead, setHeartDead] = React.useState(false);
    const intervalRef = React.useRef(null);
    const connectionListRef = React.useRef(null);
    const updateStatus = React.useCallback(() => {
      setHeartBeat(false);
      const startTime = new Date().getTime();
      if ($.WS.down || $.WS.conn.disconnected) {
        if (intervalRef.current) {
          clearInterval(intervalRef.current);
        }
        intervalRef.current = setInterval(updateStatus, 1000);
        setStatusClass('fail');
        setStatusString(
          $.WS.down
            ? 'Socket.IO server is down and/or client library failed to load'
            : 'Disconnected',
        );
        setHeartDead(true);
        return;
      }
      else if (intervalRef !== false) {
        clearInterval(intervalRef.current);
        intervalRef.current = null;
        setStatusClass('success');
        setHeartDead(false);
      }
      const $connectionList = $(connectionListRef.current);
      if ($connectionList.is(':hover')) {
        setTimeout(updateStatus, 500);
        setStatusString('Paused while hovering entries');
        return;
      }
      else setStatusString('Connected');

      $.WS.devquery('status', {}, function (data) {
        setHeartBeat(true);
        if (data.clients) {
          connsRef.current = {};
          Object.values(data.clients).forEach(client => {
            if (!connsRef.current[client.network])
              connsRef.current[client.network] = [];
            connsRef.current[client.network].push(client);
          });
          setNetworks(Object.keys(connsRef.current));
        }
        else {
          setNetworks([]);
        }
        const endTime = new Date().getTime();
        setResponseTimes([...responseTimes, endTime - startTime].slice(-responseTimeHistorySize));
        setTimeout(updateStatus, 1000);
      });
    }, []);

    const heartClass = React.useMemo(() => {
      const classes = [];
      if (heartBeat) classes.push('beat');
      if (heartDead) classes.push('dead');
      return classes.length > 0 ? classes.join(' ') : undefined;
    }, [heartBeat, heartDead]);

    React.useEffect(() => {
      updateStatus();

      return () => {
        if (intervalRef.current !== null) {
          clearInterval(intervalRef.current);
        }
      };
    }, []);

    return <>
      <h2>Status <span id="wss-heartbeat" className={heartClass}>&hearts;</span> <span id="wss-response-time">{responseTimes.length === 0 ? '…' : `${$.average(responseTimes).toFixed(0)}ms`}</span></h2>
      <div className={`notice ${statusClass}`} id="wss-status">{statusString}</div>
      <ul id="connection-list" ref={connectionListRef}>{networks.map((network, networkIndex) => {
        const networkConnections = connsRef.current[network];
        const pages = networkConnections.reduce((data, conn) => conn.page ? {
          ...data, [conn.page]: {
            since: conn.connectedSince,
          },
        } : data, {});
        const users = networkConnections.reduce((data, conn) => {
          if (!conn.user || !conn.user.name) {
            return data;
          }

          return {
            ...data,
            [conn.user.id]: conn.user.id in data ? {
              ...data[conn.user.id],
              count: data[conn.user.id].count + 1,
            } : {
              ...conn.user,
              count: 1,
            },
          };
        }, {});
        const isCurrent = networkConnections.some(conn => conn.current);
        const userIds = Object.keys(users);
        const pageKeys = Object.keys(pages);
        return <li key={network}>
          <h3>Network {networkIndex + 1}{isCurrent && <> <span className="typcn typcn-location current-icon" title="Your network" /></>}</h3>
          <p><strong>ID:</strong> <code>{network}</code></p>
          {userIds.length > 0 && <>
            <p><strong>Users:</strong></p>
            <ul>
              {userIds.map(id => {
                const { count, name } = users[id];
                if (!name) return null;
                return <li key={id}>
                  <a href={`/users/${id}`} target="_blank" rel="noreferrer">{name}</a>{count > 1 ? <> ({count})</> : null}
                </li>;
              })}
            </ul>
          </>}
          {pageKeys.length > 0 && <>
            <p><strong>Pages:</strong></p>
            <ul>{pageKeys.map(el => {
              return <li key={el}>
                <a href={el} target="_blank" rel="noreferrer">{el}</a> ({pages[el].since})
              </li>;
            })}</ul>
          </>}
        </li>;
      })}</ul>
    </>;
  };

  window.reactComponents = {
    SplitSelector,
    WSS,
  };
})();
