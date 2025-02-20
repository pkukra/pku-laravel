import React, { useState, useEffect } from "react";
import { Card } from "antd";
import moment from "moment";

export default function Index({ pasien }) {
    const [resumeData, setResumeData] = useState(null);
    const [loadingResume, setLoadingResume] = useState(false);

    const fetchResume = async () => {
        setLoadingResume(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_resume", {
                    kode_reg: pasien.FRPNOTRANSAKSI,
                })
            )
            .then((response) => {
                setResumeData(response?.data?.data || null);
                console.log(response?.data?.data);
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingResume(false);
            });
    };

    useEffect(() => {
        fetchResume();
    }, []);

    return (
        <>
            <Card title="Resume Pasien" loading={loadingResume}>
                <table
                    className="tw-table tw-table-xs"
                    style={{ width: "100%" }}
                >
                    <tbody align="left">
                        <tr>
                            <th style={{ width: "25%", verticalAlign: "top" }}>
                                Anamnesa (S)
                            </th>
                            <td
                                style={{ verticalAlign: "top" }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_ANAMNESA,
                                }}
                            ></td>
                        </tr>
                        <tr>
                            <th style={{ verticalAlign: "top" }}>
                                Pemeriksaan Fisik (O)
                            </th>
                            <td
                                style={{ verticalAlign: "top" }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_CATATAN_FISIK,
                                }}
                            ></td>
                        </tr>
                        <tr>
                            <th style={{ verticalAlign: "top" }}>
                                Diagnosa (A)
                            </th>
                            <td
                                style={{ verticalAlign: "top" }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_DIAGNOSA,
                                }}
                            ></td>
                        </tr>
                        <tr>
                            <th style={{ verticalAlign: "top" }}>
                                Tindakan/Planning (P)
                            </th>
                            <td
                                style={{ verticalAlign: "top" }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_PLANNING,
                                }}
                            ></td>
                        </tr>
                    </tbody>
                </table>
            </Card>
        </>
    );
}
