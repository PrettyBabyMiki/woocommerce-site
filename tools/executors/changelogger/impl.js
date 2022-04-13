"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var __rest = (this && this.__rest) || function (s, e) {
    var t = {};
    for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
        t[p] = s[p];
    if (s != null && typeof Object.getOwnPropertySymbols === "function")
        for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
            if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i]))
                t[p[i]] = s[p[i]];
        }
    return t;
};
exports.__esModule = true;
var child_process_1 = require("child_process");
var path_1 = require("path");
var fs_1 = require("fs");
var chalk = require("chalk");
var changeloggerScriptPath = 'vendor/bin/changelogger';
function runChangelogger(_a) {
    var action = _a.action, cwd = _a.cwd, extraArgs = __rest(_a, ["action", "cwd"]);
    return __awaiter(this, void 0, void 0, function () {
        return __generator(this, function (_b) {
            return [2 /*return*/, new Promise(function (resolve, reject) {
                    var args = [action].concat(
                    // Add any extra arguments supplied. NX camel cases and converts values to Numbers.
                    // Undo all that so arguments can be passed to Jetpack Changelogger unmodified.
                    Object.keys(extraArgs).map(function (key) {
                        return "--" + key.replace(/[A-Z]/g, function (m) { return '-' + m.toLowerCase(); }) + "=" + (Number(extraArgs[key]) && action === 'write'
                            ? extraArgs[key].toFixed(1)
                            : extraArgs[key]);
                    }));
                    var changeloggerScript = (0, child_process_1.spawn)("./" + changeloggerScriptPath, args, {
                        stdio: 'inherit'
                    });
                    changeloggerScript.on('close', function (code) {
                        resolve({ code: code, error: undefined });
                    });
                    changeloggerScript.on('error', function (error) {
                        reject({ code: 1, error: error });
                    });
                })];
        });
    });
}
function changelogExecutor(options, context) {
    return __awaiter(this, void 0, void 0, function () {
        var cwd, projectPath, _a, code, error;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    cwd = options.cwd;
                    projectPath = (0, path_1.join)(__dirname, '../../../', cwd);
                    console.info(chalk.cyan("\nExecuting Changelogger...\n"));
                    try {
                        process.chdir(projectPath);
                        console.log(chalk.yellow('Executing from directory: ' + process.cwd() + '\n'));
                    }
                    catch (error) {
                        console.error(chalk.bgRed('Unable to find project working directory'));
                        console.error(error);
                        return [2 /*return*/, { success: false }];
                    }
                    if (!(0, fs_1.existsSync)(changeloggerScriptPath)) {
                        console.error(chalk.bgRed('Changelogger scripts not found. Did you remember to `composer install` from project directory?'));
                        return [2 /*return*/, { success: false }];
                    }
                    return [4 /*yield*/, runChangelogger(options)];
                case 1:
                    _a = _b.sent(), code = _a.code, error = _a.error;
                    if (error) {
                        console.error(chalk.bgRed(error));
                        return [2 /*return*/, { success: false }];
                    }
                    return [2 /*return*/, { success: code === 0 }];
            }
        });
    });
}
exports["default"] = changelogExecutor;
