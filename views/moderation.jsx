import React from 'react'
import ReactDOM from 'react-dom'
const bsFetch = window.blessing.fetch
const toast = window.blessing.notify.toast
import Pagination from './components/Pagination'
const t = window.trans
const Actions = ({ data, onSubmit }) => {
  return <div className="col-lg-4">
    <div className="card card-primary">
      <div className="card-header">
        <h3 className="card-title">{t('texture-moderation.moderate.machine-result')}</h3>
      </div>
      <div className="card-body">
        <div className="container-fluid">
          {[[t('texture-moderation.moderate.porn-score'), 'porn_score'], [t('texture-moderation.moderate.porn-label'), 'porn_label'], [t('texture-moderation.moderate.politics-score'), 'politics_score'], [t('texture-moderation.moderate.politics-label'), 'politics_label']].map((v, i) => (
            <div className="row mb-3">
              <div className="col-sm-4">{v[0]}</div>
              <div className="col-sm-8">{(i === 0 && data[v[1]] > 60)
                ? (<><b>{data[v[1]]}</b>
                  <i class="fas fa-exclamation-circle ml-2"></i></>)
                : data[v[1]]}</div>
            </div>
          ))}
        </div>
      </div>
      <div className="card-footer">
        <div className="float-left">
          <a className="btn btn-danger mr-2" onClick={() => onSubmit('reject')}>
            {t('texture-moderation.moderate.reject')}
          </a>
          <a className="btn btn-warning mr-2" onClick={() => onSubmit('private')}>
          {t('texture-moderation.moderate.privacy')}
          </a>
          <a className="btn btn-primary mr-2" onClick={() => onSubmit('approve')}>
          {t('texture-moderation.moderate.approve')}
          </a>
        </div>
      </div>
    </div>
    <div className="card">
      <div className="card-header">
        <h3 className="card-title">
          {t('texture-moderation.original')}
        </h3>
      </div>
      <div className="card-body">
        <img src={'/raw/' + data.tid} />
      </div>
    </div>
  </div>
}

const App = () => {
  const [list, setList] = React.useState([])
  const states = window.states

  const [viewing, setViewing] = React.useState(null)
  const [page, setPage] = React.useState(1)
  const [maxPage, setMaxPage] = React.useState(1)
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
    setMaxPage(last_page)
  }
  const submit = async (action) => {
    let r = await bsFetch.put('/admin/texture-moderation/review/' + viewing.id, {
      action
    })
    if (r.code === 0) {
      toast.success(r.message)
      setViewing(null)
      update()
    } else {
      toast.error(r.message)
    }
  }
  React.useEffect(() => {
    update()
  }, [page])

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
            <div className="card mr-3 mb-3" style={{ width: 240 }} onClick={() => setViewing(v)}>
              <div className="card-header">
                {v.uploader ? (
                  <><b>{t('texture-moderation.uploader')}：</b>
                    <span className="mr-1">{v.nickname}</span>
                    (UID:
                    {v.uploader})
                  </>
                ) :
                  <b>{t('texture-moderation.deleted')}</b>}
              </div>
              <div className="card-body">
                <img src={`/preview/${v.tid}?height=250`} className="card-img-top" />
              </div>
              <div className="card-footer">
                <div className="d-flex">
                  <span className={"badge "
                    + ([0, 5].includes(v.review_state) ? 'bg-warning' :
                      [1, 3].includes(v.review_state) ? 'bg-green' :
                        v.review_state === 2 ? 'bg-danger' :
                          v.review_state === 4 ? 'bg-gray' : '')
                  }>{states[v.review_state]}</span>
                  <span className="badge bg-info ml-1">TID:
                    {v.tid}</span>
                  <div className="dropdown"></div>
                </div>
                <div>
                  <b>{t('texture-moderation.operator')}: </b>
                  {v.operator ? (
                    <>{v.operator_nickname} (UID:
                      {v.operator})</>
                  ) : <>{t('texture-moderation.machine-review')}</>}
                </div>
                <div>
                  <b>{t('texture-moderation.record-source')}: </b>
                  {v.source === 0 ? t('texture-moderation.source.on-public-upload') : 
                  (v.source === 1 ? t('texture-moderation.source.on-privacy-updated') : 
                  t('texture-moderation.source.unknown'))}
                </div>
                <div>
                  <b>{t('texture-moderation.last-reviewed')}: </b>
                  {new Date(v.updated_at).toLocaleString()}
                </div>
              </div>
            </div>
          ))}
        </div>
        <div className="card-footer">
          <Pagination
            page={page}
            totalPages={maxPage}
            onChange={setPage}
          />
        </div>
      </div>
    </div>
    {viewing && <Actions data={viewing} onSubmit={submit} />}
  </>
}

ReactDOM.render(<App />, document.getElementById("moderation_root"))
