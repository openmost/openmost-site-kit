import {ref, readonly} from "vue";

const state = ref("");

export function useState(initialState) {
    state.value = initialState;
    const setState = (newState) => {
        state.value = newState;
    };

    return [readonly(state), setState];
}