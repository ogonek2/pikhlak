import { reactive } from 'vue';
import axios from 'axios';
import { nestedUrl } from './format';

export function useClientProfile(initial) {
    const profile = reactive({
        client: structuredClone(initial.client),
        summary: structuredClone(initial.summary),
        config: structuredClone(initial.config),
        urls: structuredClone(initial.urls),
        loading: false,
        message: '',
        errors: {},
    });

    function applyPayload(data) {
        Object.assign(profile.client, structuredClone(data.client));
        Object.assign(profile.summary, structuredClone(data.summary));
        Object.assign(profile.config, structuredClone(data.config));
        Object.assign(profile.urls, structuredClone(data.urls));
    }

    function flash(text, isError = false) {
        profile.message = text;
        if (!isError) {
            setTimeout(() => {
                if (profile.message === text) {
                    profile.message = '';
                }
            }, 3500);
        }
    }

    function clearErrors() {
        profile.errors = {};
    }

    function setValidationErrors(err) {
        profile.errors = err.response?.data?.errors ?? {};
        flash(err.response?.data?.message ?? 'Проверьте форму', true);
    }

    async function request(method, url, payload = null) {
        profile.loading = true;
        clearErrors();
        try {
            const response = await axios({
                method,
                url,
                data: payload ?? undefined,
            });
            if (response.data?.data) {
                applyPayload(response.data.data);
            }
            if (response.data?.message) {
                flash(response.data.message);
            }
            return response.data;
        } catch (err) {
            if (err.response?.status === 422) {
                setValidationErrors(err);
            } else {
                flash(err.response?.data?.message ?? 'Ошибка запроса', true);
            }
            throw err;
        } finally {
            profile.loading = false;
        }
    }

    profile.applyPayload = applyPayload;
    profile.flash = flash;
    profile.clearErrors = clearErrors;
    profile.updateProfile = (payload) => request('put', profile.urls.update, payload);
    profile.claimTelegram = () => request('post', profile.urls.claimTelegram);
    profile.addPhone = (payload) => request('post', profile.urls.phones.store, payload);
    profile.removePhone = (id) => request('delete', nestedUrl(profile.urls.phones.destroy, id));
    profile.addVehicle = (payload) => request('post', profile.urls.vehicles.store, payload);
    profile.updateVehicle = (id, payload) => request('put', nestedUrl(profile.urls.vehicles.update, id), payload);
    profile.removeVehicle = (id) => request('delete', nestedUrl(profile.urls.vehicles.destroy, id));
    profile.addContract = (payload) => request('post', profile.urls.contracts.store, payload);
    profile.updateContract = (id, payload) => request('put', nestedUrl(profile.urls.contracts.update, id), payload);
    profile.removeContract = (id) => request('delete', nestedUrl(profile.urls.contracts.destroy, id));
    profile.addPayment = (payload) => request('post', profile.urls.payments.store, payload);
    profile.updatePayment = (id, payload) => request('put', nestedUrl(profile.urls.payments.update, id), payload);
    profile.removePayment = (id) => request('delete', nestedUrl(profile.urls.payments.destroy, id));
    profile.markPaymentPaid = (id) => request('post', nestedUrl(profile.urls.payments.paid, id));
    profile.addInsurance = (payload) => request('post', profile.urls.insurances.store, payload);
    profile.updateInsurance = (id, payload) => request('put', nestedUrl(profile.urls.insurances.update, id), payload);
    profile.removeInsurance = (id) => request('delete', nestedUrl(profile.urls.insurances.destroy, id));
    profile.addMaintenance = (payload) => request('post', profile.urls.maintenances.store, payload);
    profile.updateMaintenance = (id, payload) => request('put', nestedUrl(profile.urls.maintenances.update, id), payload);
    profile.removeMaintenance = (id) => request('delete', nestedUrl(profile.urls.maintenances.destroy, id));

    return profile;
}
