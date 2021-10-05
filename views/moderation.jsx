const React = window.React
const ReactDOM = window.ReactDOM
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

const App = () => {
  const list = window.data.data
  const states = window.states
  return <>
    <div className="col-lg-8">
      <div className="card">
        <div className="card-header">
          <form className="input-group">
            <input type="text" className="form-control" title="搜索" />
          </form>
        </div>
        <div className="card-body p-0 d-flex flex-wrap">
          {list.map(v => (
            <div className="card mr-3 mb-3">
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
          <div className="float-left">
            <a className="btn btn-primary float-left mr-2" href="?type=5">Pending</a>
            <a className="btn btn-primary float-left mr-2" href="?type=0">Manual</a>
            <a className="btn btn-success float-left mr-2" href="?type=1">Accepted</a>
            <a className="btn btn-danger" href="?type=2">Rejected</a>
          </div>
        </div>
      </div>
    </div>
    <div className="col-lg-4"></div>
  </>
}

ReactDOM.render(<App />, document.getElementById("moderation_root"))