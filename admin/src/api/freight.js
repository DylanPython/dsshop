import request from '@/utils/request'
import Qs from 'qs'
export function getList(query) {
  return request({
    url: 'freight',
    method: 'get',
    params: query
  })
}

export function createSubmit(data) {
  data = Qs.parse({
    data
  })
  data = data.data
  return request({
    url: 'freight',
    method: 'POST',
    data
  })
}

export function updateSubmit(id, data) {
  data = Qs.parse({
    data
  })
  data = data.data
  return request({
    url: 'freight/' + id,
    method: 'PUT',
    data
  })
}

export function setDelete(id, data) {
  data = Qs.parse({
    data
  })
  data = data.data
  return request({
    url: 'freight/' + id,
    method: 'DELETE',
    data
  })
}

export function getShow(id) {
  return request({
    url: 'freight/' + id,
    method: 'GET'
  })
}
