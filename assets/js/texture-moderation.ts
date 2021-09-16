import { fetch, notify } from 'blessing-skin'

function texture_moderation_accept(id: Number) {
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

function texture_moderation_reject(id: Number) {
  let reason: string = ''

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