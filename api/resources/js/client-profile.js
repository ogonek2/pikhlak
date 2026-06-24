import './bootstrap';
import { createApp } from 'vue';
import ClientProfileApp from './client-profile/ClientProfileApp.vue';

const el = document.getElementById('client-profile-app');
const payload = window.__CLIENT_PROFILE__;

if (el && payload) {
    createApp(ClientProfileApp, { initial: payload }).mount(el);
}
