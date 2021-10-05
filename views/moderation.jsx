const React = window.React
const ReactDOM = window.ReactDOM
const bsFetch = window.blessing.fetch

/**
 * 
 * @returns const {fetch, notify} = window.blessing

function texture_moderation_accept(id) {
  notify.showModal({
    'title': `通过 TID: ${id} 的审核`
  }).then(() => {
    fetch.post('/admin/texture-moderation/review', {
      'id': id,
      'action': 'accept',
    }).then(response => {
      if (response.code === 0) {
        notify.toast.success(response.message)
        location.reload()
      } else {
        notify.toast.error(response.message)
      }
    }).catch(() => {

    })
  }).catch(() => {

  })
}

function texture_moderation_reject(id) {
  let reason = ''

  notify.showModal({
    'mode': 'prompt',
    'title': `拒绝 TID: ${id} 的审核`,
    'text': '请输入理由：',
    'placeholder': '理由',
  }).then(result => {
    reason = result.value
  }).then(() => {
    fetch.post('/admin/texture-moderation/review', {
      'id': id,
      'action': 'reject',
      'reason': reason,
    }).then(response => {
      if (response.code === 0) {
        notify.toast.success(response.message)
        location.reload()
      } else {
        notify.toast.error(response.message)
      }
    }).catch(() => {

    })
  })
  .catch(() => {

  })
}

Object.assign(window, { texture_moderation_accept, texture_moderation_reject })

 */

const Actions = ({ data, onSubmit }) => {
  return <div className="col-lg-4">
    <div className="card card-primary">
      <div className="card-header">
        <h3 className="card-title">机审数据</h3>
      </div>
      <div className="card-body">
        <div className="container-fluid">
          {[['鉴黄得分', 'porn_score'], ['鉴黄标签', 'porn_label'], ['鉴政得分', 'politics_score'], ['鉴政标签', 'politics_label']].map(v => (
            <div className="row mb-3">
              <div className="col-sm-4">{v[0]}</div>
              <div className="col-sm-8">{data[v[1]]}</div>
            </div>
          ))}
        </div>
      </div>
      <div className="card-footer">
        <div className="float-left">
          <a className="btn btn-danger mr-2" onClick={() => onSubmit('reject')}>审核拒绝</a>
          <a className="btn btn-warning mr-2" onClick={() => onSubmit('private')}>设为私密</a>
          <a className="btn btn-primary mr-2" onClick={() => onSubmit('accept')}>审核通过</a>
        </div>
      </div>
    </div>
  </div>
}

const App = () => {
  const [list, setList] = React.useState([])
  const states = window.states

  const [viewing, setViewing] = React.useState(null)
  const [page, setPage] = React.useState(1)
  const [input, setInput] = React.useState('review_state:0 sort:-created_at')
  const onSubmit = (e) => {
    e.preventDefault()
    update()
  }
  const update = async () => {
    const { data, last_page } = await bsFetch.get(
      '/admin/texture-moderation/list',
      {
        q: input,
        page,
      },
    )
    setList(data)
  }
  const submit = async (action) => {
    await bsFetch.post('/admin/texture-moderation/review', {
      id: viewing.id,
      action
    })
    setViewing(null)
    update()
  }
  React.useEffect(() => {
    update()
  }, [])

  return <>
    <div className="col-lg-8">
      <div className="card">
        <div className="card-header">
          <form className="input-group" onSubmit={onSubmit}>
            <input type="text" className="form-control" title="搜索"
              value={input} onChange={(e) => setInput(e.target.value)}
            />
            <div className="input-group-append">
              <button className="btn btn-primary" type="submit">搜索</button>
            </div>
          </form>
        </div>
        <div className="card-body d-flex flex-wrap">
          {list.map(v => (
            <div className="card mr-3 mb-3" style={{width: 240}} onClick={() => setViewing(v)}>
              <div className="card-header">
                <b>上传者</b>
                <span className="mr-1">{v.nickname}</span>
                (UID:
                {v.uploader})
              </div>
              <div className="card-body">
                <img src={`/preview/${v.tid}?height=250`} className="card-img-top" />
              </div>
              <div className="card-footer">
                <div className="d-flex justify-content-between">
                  <span className="badge bg-warning">{states[v.review_state]}</span>
                  <span className="badge bg-info ml-1">TID:
                    {v.tid}</span>
                  <div className="dropdown"></div>
                </div>
                <div>
                  <b>审核人：</b>
                  (UID:
                  {v.operator})
                </div>
                <div>
                  <b>审核时间：</b>
                  {v.updated_at}
                </div>
              </div>
            </div>
          ))}
        </div>
        <div className="card-footer">

        </div>
      </div>
    </div>
    {viewing && <Actions data={viewing} onSubmit={submit} />}
  </>
}

ReactDOM.render(<App />, document.getElementById("moderation_root"))