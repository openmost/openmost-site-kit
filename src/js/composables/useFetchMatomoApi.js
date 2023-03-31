import {ref} from "vue";

export function useFetchMatomoApi(request) {

    let values = ref();

    return fetch(request)
        .then((response) => response.json())
        .then((data) => data)
}