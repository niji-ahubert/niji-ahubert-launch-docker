import {startStimulusApp} from '@symfony/stimulus-bundle';
import DockerLogsController from './controllers/docker_logs_controller.js';
import ProjectFormController from './controllers/project_form_controller.js';
import ServiceProjectFormController from './controllers/service_project_form_controller.js';
import ServiceExternalFormController from './controllers/service_external_form_controller.js';
import ClientFormController from './controllers/client_form_controller.js';
import {FieldNormalizer} from './js/modules/field-normalizer.js';

// Expose FieldNormalizer globally BEFORE starting Stimulus
window.FieldNormalizer = FieldNormalizer;

const app = startStimulusApp();


// register any custom, 3rd party controllers here
app.register('docker-logs', DockerLogsController);
app.register('project-form', ProjectFormController);
app.register('service-project-form', ServiceProjectFormController);
app.register('service-external-form', ServiceExternalFormController);
app.register('client-form', ClientFormController);